<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }
}
