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
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(UserController::class)
    ->name('user.')
    ->group(function () {
        Route::post('register', 'create')->name('create');
    });

Route::controller(SessionController::class)
    ->name('session.')
    ->group(function () {
        Route::post('login', 'create')->name('create');
    });

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::controller(FileController::class)
            ->name('files.')
            ->group(function () {
                Route::get('files', 'index')->name('index');
                Route::post('files/{folder?}', 'store')->name('store');
            });

        Route::controller(UserController::class)
            ->name('user.')
            ->group(function () {
                Route::get('user', 'show')->name('show');
            });
    });
