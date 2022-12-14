<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public const RESTRICTED_MIME_TYPES = [
        'text/php',
        'text/x-php',
        'application/php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
    ];

    public const RESTRICTED_FILE_EXTENSIONS = [
        'php',
    ];

    public const MAX_FILE_SIZE = 20 * 1024 * 1024;
    public const MAX_STORAGE_SIZE = 100 * 1024 * 1024;

    /**
     * List all files inside the folder.
     *
     * @param JsonResponseHelper $response
     * @param Folder|null $folder In case this parameter is `null`, returns files located directly inside the root folder.
     * @return JsonResponse
     */
    public function index(JsonResponseHelper $response, ?Folder $folder = null): JsonResponse
    {
        $folder ??= auth()->user()->rootFolder;
        $files = $folder
            ->files()
            ->get()
            ->map(fn(File $fileModel) => $fileModel->only(['id', 'name', 'size', 'created_at']));

        return $response
            ->withData(['files' => $files])
            ->ok();
    }

    /**
     * Stores the file inside the folder.
     *
     * Required parameters:
     * - file: must be just a single file (no arrays allowed), PHP files are not allowed,
     *   files >20Mb are not allowed, storing more than 100Mb of data at once is not allowed.
     *
     * Response contains the ID of a newly created file.
     *
     * @param JsonResponseHelper $response
     * @param Request $request
     * @param Folder|null $folder In case this parameter is `null`, stores the file directly inside the root folder.
     * @return JsonResponse
     */
    public function store(
        JsonResponseHelper $response,
        Request            $request,
        ?Folder            $folder = null,
    ): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required'],
        ]);

        if ($validator->fails()) {
            return $response
                ->withData($validator->errors()->toArray())
                ->badRequest();
        }
        if (is_array($request->file('file'))) {
            return $response
                ->withData(['file' => 'Only single file per request is allowed.'])
                ->badRequest();
        }

        $rootFolder = auth()->user()->rootFolder;
        $fileFolder = $folder ?? $rootFolder;

        $file = $request->file('file');
        $fileSize = $file->getSize();

        if ($fileSize > self::MAX_FILE_SIZE) {
            $fileSizeError = sprintf('Files can be at most %dMb large', self::MAX_FILE_SIZE / 1024 / 1024);
            return $response
                ->withData(['file' => $fileSizeError])
                ->badRequest();
        }
        if ($rootFolder->size + $fileSize > self::MAX_FILE_SIZE) {
            $fileSizeError = sprintf('You can\'t upload more than %dMb to the cloud', self::MAX_STORAGE_SIZE / 1024 / 1024);
            return $response
                ->withData(['file' => $fileSizeError])
                ->badRequest();
        }

        if (
            in_array(strtolower($file->getClientOriginalExtension()), self::RESTRICTED_FILE_EXTENSIONS) ||
            in_array($file->getClientMimeType(), self::RESTRICTED_MIME_TYPES) ||
            in_array($file->getMimeType(), self::RESTRICTED_MIME_TYPES)
        ) {
            return $response
                ->withData(['file' => 'Uploading this type of file is not supported.'])
                ->badRequest();
        }

        $filePath = $file->store('user_files');

        $newFile = DB::transaction(function () use ($rootFolder, $file, $filePath, $fileSize, $fileFolder) {
            $fileFolder->size = $fileFolder->size + $fileSize;
            $fileFolder->save();

            if ($fileFolder->id !== $rootFolder->id) {
                $rootFolder->size = $rootFolder->size + $fileSize;
                $rootFolder->save();
            }

            return File::query()->create([
                'name' => Str::limit($file->getClientOriginalName(), limit: 255),
                'path' => $filePath,
                'size' => $fileSize,
                'owner_id' => auth()->id(),
                'folder_id' => $fileFolder->id,
            ]);
        });

        return $response
            ->withData([
                'file' => ['id' => $newFile->id],
            ])
            ->ok();
    }

    /**
     * Update the metadata of the file.
     *
     * Optional parameters:
     * - name: at most 255 characters long, does __not__ have to be a unique name, you can have
     *   several files with the same name inside the folder.
     *
     * @param File $file
     * @param Request $request
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function update(
        File               $file,
        Request            $request,
        JsonResponseHelper $response,
    ): JsonResponse
    {
        if ($request->user()->cannot('update', $file)) {
            return $response->forbidden();
        }

        $validator = validator::make($request->all(), [
            'name' => ['max:255'],
        ]);
        if ($validator->fails()) {
            return $response->withdata($validator->errors()->toarray())->badRequest();
        }
        $validatedData = $validator->safe()->only(['name']);

        $validatedData['name'] = $validatedData['name'] ?? $file->name;

        $file->name = $validatedData['name'];
        if (!$file->save()) {
            return $response->serverError();
        }

        return $response->ok();
    }

    /**
     * Deletes the specified file and frees the space of the user's cloud storage.
     *
     * @param File $file
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function delete(
        File               $file,
        JsonResponseHelper $response,
        Request            $request,
    ): JsonResponse
    {
        if ($request->user()->cannot('delete', $file)) {
            return $response->forbidden();
        }

        DB::transaction(function () use ($file) {
            $rootFolder = auth()->user()->rootFolder;
            $parentFolder = $file->folder;

            $rootFolder->size = $rootFolder->size - $file->size;
            $rootFolder->save();

            if ($rootFolder->id !== $parentFolder->id) {
                $parentFolder->size = $parentFolder->size - $file->size;
                $parentFolder->save();
            }

            $file->delete();
        });

        return $response->ok();
    }

    /**
     * Downloads the specified file.
     *
     * @param File $file
     * @param JsonResponseHelper $response
     * @return StreamedResponse|JsonResponse
     */
    public function show(
        File               $file,
        JsonResponseHelper $response,
        Request            $request,
    ): StreamedResponse|JsonResponse
    {
        if ($request->user()->cannot('view', $file)) {
            return $response->forbidden();
        }

        return Storage::download($file->path, $file->name);
    }
}
