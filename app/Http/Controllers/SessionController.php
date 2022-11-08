<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class SessionController extends Controller
{
    /**
     * Logs in a user with the provided credentials.
     * ````
     * POST /api/login
     * ````
     *
     * Required parameters:
     * - email:
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
                    'message' => 'You are already logged in.',
                ],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'data' => $validator->errors(),
            ], status: Response::HTTP_BAD_REQUEST);
        }

        $validatedCredentials = $validator->safe()->only(['email', 'password']);
        if (auth()->attempt($validatedCredentials)) {
            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'fail',
            'data' => [
                'email' => 'The provided credentials do not match our records.',
            ],
        ], status: Response::HTTP_UNAUTHORIZED);
    }
}
