<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\File;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $files = $user
            ->files()
            ->get()
            ->map(fn(File $fileModel) => $fileModel->only(['id', 'name', 'created_at']));

        return response()->json([
            'status' => 'success',
            'data' => $files,
        ]);
    }

    public function store(
        JsonResponseHelper $response,
        Request            $request,
        string             $folder = null,
    ): JsonResponse
    {
        if ($folder !== null) {
            // TODO: Implement saving files to folders
            throw new \Exception('Not implemented');
        }

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'max:20000'],
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

        $file = $request->file('file');

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

        $fileModel = File::query()->create([
            'name' => Str::limit($file->getClientOriginalName(), limit: 255),
            'path' => $filePath,
            'owner_id' => auth()->id(),
            'folder_id' => null,
        ]);

        if (!$fileModel->exists()) {
            return $response->serverError();
        }

        return $response->ok();
    }

    public function update(
        File               $file,
        Request            $request,
        JsonResponseHelper $response,
    ): JsonResponse
    {
        if ($file->owner->id !== auth()->id()) {
            return $response->unauthorized();
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

        return $response
            ->withData(['file' => $file->only(['id', 'name', 'created_at'])])
            ->ok();
    }

    public function delete(
        File               $file,
        Request            $request,
        JsonResponseHelper $response,
    ): JsonResponse
    {
        if ($file->owner->id !== auth()->id()) {
            return $response->unauthorized();
        }

        if (!$file->delete()) {
            return $response->serverError();
        }

        return $response->ok();
    }
}
