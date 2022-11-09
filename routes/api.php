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
use App\Http\Controllers\FolderController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(UserController::class)->name('user.')->group(function () {
    Route::post('register', 'create')->name('create');
});

Route::controller(SessionController::class)->name('session.')->group(function () {
    Route::post('login', 'create')->name('create');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(FileController::class)
        ->name('files.')
        ->prefix('files')
        ->group(function () {
            Route::get('/folder/{folder:id}', 'index')->name('index');
            Route::get('', 'index')->name('index');

            Route::post('/folder/{folder:id?}', 'store')->name('store');
            Route::post('', 'store')->name('store');

            Route::patch('/id/{file:id}', 'update')->name('update');
            Route::delete('/{file:id}', 'delete')->name('delete');
            Route::get('/id/{file:id}', 'show')->name('show');
        });

    Route::controller(FolderController::class)
        ->name('folders.')
        ->prefix('folders')
        ->group(function () {
            Route::get('', 'index')->name('index');
            Route::get('/root', 'getRootFolder')->name('index');

            Route::post('', 'store')->name('store');
            Route::post('/root', 'store')->name('store');

            Route::delete('/{folder:id}', 'delete')->name('delete');

            Route::get('/{folder:id}', 'show')->name('show');
        });

    Route::controller(UserController::class)->name('user.')->group(function () {
        Route::get('user', 'show')->name('show');
    });
});
