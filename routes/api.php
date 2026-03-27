<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'tokenLogin']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::group(['prefix' => 'me'], function () {
        Route::get('/', [AuthController::class, 'me']);
        Route::delete('/', [AuthController::class, 'closeAccount']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});
