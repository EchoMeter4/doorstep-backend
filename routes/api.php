<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\PassController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ZoneAccessController;

Route::post('/login', [AuthController::class, 'tokenLogin']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('me')->group(function () {
        Route::get('/', [UserController::class, 'me']);
        Route::patch('/', [UserController::class, 'updateMe']);
        Route::delete('/', [UserController::class, 'deleteMe']);
    });

    Route::get('logs', [AccessLogController::class, 'index']);
    Route::post('/access', [ZoneAccessController::class, 'attempt']);

    Route::apiResource('users', UserController::class);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('zones', ZoneController::class);
    Route::apiResource('visitors', VisitorController::class);
    Route::apiResource('organizations', OrganizationController::class);
    Route::apiResource('credentials', CredentialController::class);
    Route::apiResource('passes', PassController::class);
    Route::apiResource('vehicles', VehicleController::class);
});
