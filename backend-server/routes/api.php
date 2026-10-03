<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {

    // Public Routes
    Route::get('/status', function () {
        return response()->json([
            'app_name' => 'Academia Vault',
            'status' => 'Active',
            'protocol' => 'Decentralized Mesh v1.0',
            'server_time' => now()->toDateTimeString(),
        ]);
    });

    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/biometric-register', [RegistrationController::class, 'register']);

    // Protected Routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/search', [SearchController::class, 'search']);
        Route::get('/user/profile', [UserController::class, 'profile']);
    });

});