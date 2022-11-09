<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    private const PUBLIC_FOLDER_ATTRIBUTES = ['id', 'name', 'size'];

    /**
     * Lists all folders created by the authenticated user (except for the implicitly created root
     * folder): their IDs, names and space taken (in bytes).
     *
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function index(JsonResponseHelper $response): JsonResponse
    {
        $folders = auth()
            ->user()
            ->rootfolder
            ->nestedFolders
            ->map(fn(Folder $folder) => $folder->only(self::PUBLIC_FOLDER_ATTRIBUTES))
            ->toArray();

        return $response
            ->withData(['folders' => $folders])
            ->ok();
    }

    /**
     * Shows the information about the root folder. This includes the total space taken by all
     * user's files.
     *
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function getRootFolder(JsonResponseHelper $response): JsonResponse
    {
        return $response
            ->withData(['root' => auth()->user()->rootFolder->only(self::PUBLIC_FOLDER_ATTRIBUTES)])
            ->ok();
    }

    /**
     * Create a new folder.
     *
     * Required parameters:
     * - name: at most 255 characters long, does __not__ have to be a unique folder name.
     *
     * @param Request $request
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function store(Request $request, JsonResponseHelper $response): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:255'],
        ]);
        if ($validator->fails()) {
            return $response
                ->withData($validator->errors()->toArray())
                ->badRequest();
        }
        $validatedFolderData = $validator->safe()->only(['name']);

        $newFolder = Folder::query()->create([
            'name' => $validatedFolderData['name'],
            'parent_folder_id' => auth()->user()->rootFolder->id,
        ]);

        return $response
            ->withData([
                'folder' => ['id' => $newFolder->id],
            ])
            ->ok();
    }

    /**
     * Delete the folder and all the files inside it.
     *
     * @param Folder $folder
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function delete(Folder $folder, JsonResponseHelper $response): JsonResponse
    {
        // Users are not allowed to delete their root folder
        if ($folder->id === auth()->user()->rootFolder->id) {
            return $response
                ->withMessage('Deleting the root folder is not allowed')
                ->forbidden();
        }
        if ($folder->parentFolder->id !== auth()->user()->rootFolder->id) {
            return $response->forbidden();
        }

        DB::transaction(function () use ($folder) {
            $rootFolder = auth()->user()->rootFolder;
            $rootFolder->size = $rootFolder->size - $folder->size;
            $rootFolder->save();

            foreach ($folder->files as $file) {
                $file->delete();
            }
            $folder->delete();
        });

        return $response->ok();
    }

    /**
     * Show info about the specific folder: ID, name and size taken by the files inside the folder.
     *
     * @param JsonResponseHelper $response
     * @param Folder $folder
     * @return JsonResponse
     */
    public function show(JsonResponseHelper $response, Folder $folder)
    {
        if ($folder->parentFolder->id !== auth()->user()->rootFolder->id) {
            return $response->forbidden();
        }

        return $response
            ->withData(['folder' => $folder->only(self::PUBLIC_FOLDER_ATTRIBUTES)])
            ->ok();
    }
}
