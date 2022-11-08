<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\Http\Controllers\FileController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::controller(UserController::class)->group(function () {
    Route::post('register', 'create');
});

Route::controller(SessionController::class)->group(function () {
    Route::post('login', 'create');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(FileController::class)->group(function () {
        Route::get('files', 'index');
    });
});
