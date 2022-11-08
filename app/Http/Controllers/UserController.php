<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        if (auth()->check()) {
            return response()->json([
                'status' => 'fail',
                'data' => [
                    'message' => 'Cannot register a new user, because you are currently logged in.',
                ],
            ], status: Response::HTTP_BAD_REQUEST);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users', 'max:255'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors(),
            ], status: Response::HTTP_BAD_REQUEST);
        }

        $validatedUserData = $validator->safe()->only(['name', 'email', 'password']);
        $newUser = User::query()->create([
            'name' => $validatedUserData['name'],
            'email' => $validatedUserData['email'],
            'password' => Hash::make($validatedUserData['password']),
        ]);

        if (!$newUser->exists()) {
            return response()->json([
                'status' => 'fail',
                'data' => null,
            ], status: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        auth()->login($newUser);
        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'data' => null,
        ]);
    }
}
