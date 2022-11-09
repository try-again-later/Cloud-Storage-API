<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
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
     * @param JsonResponseHelper $response
     * @return JsonResponse
     */
    public function create(Request $request, JsonResponseHelper $response): JsonResponse
    {
        if (auth()->check()) {
            return $response
                ->withMessage('You are already logged in.')
                ->badRequest();
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return $response
                ->withData($validator->errors()->all())
                ->badRequest();
        }

        $validatedCredentials = $validator->safe()->only(['email', 'password']);
        if (auth()->attempt($validatedCredentials)) {
            return $response->ok();
        }

        return $response
            ->withData(['email' => 'The provided credentials do not match our records.'])
            ->forbidden();
    }
}
