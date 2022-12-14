<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Creates a new user with the given credentials. Also, automatically logs in a newly created
     * user.
     * ```
     * POST /api/register
     * ```
     *
     * Required parameters:
     * - name: at most 255 characters long
     * - email: must be a proper not yet registered email address, at most 255 characters long
     * - password
     *
     * @param Request $request
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function create(Request $request, JsonResponseHelper $response): JsonResponse
    {
        if (auth()->check()) {
            return $response
                ->withMessage('Cannot register a new user, because you are currently logged in.')
                ->badRequest();
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users', 'max:255'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return $response
                ->withData($validator->errors()->toArray())
                ->badRequest();
        }

        $validatedUserData = $validator->safe()->only(['name', 'email', 'password']);

        DB::transaction(function () use ($validatedUserData) {
            $rootFolder = Folder::query()->create([
                // the name doesn't really matter since that's the root folder
                'name' => '/',
            ]);

            $newUser = User::query()->create([
                'name' => $validatedUserData['name'],
                'email' => $validatedUserData['email'],
                'password' => Hash::make($validatedUserData['password']),
                'root_folder_id' => $rootFolder->id,
            ]);

            auth()->login($newUser);
        });

        return $response->ok();
    }

    public function show(JsonResponseHelper $response): JsonResponse
    {
        return $response
            ->withData(['user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ]])
            ->ok();
    }
}
