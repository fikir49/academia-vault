<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegistrationController;
use App\Http\Controllers\API\AuthController;
Route::get("/v1/status", function () {
    return response()->json([
        "app_name" => "Academia Vault",
        "status" => "Active",
        "protocol" => "Decentralized Mesh v1.0",
        "server_time" => now()->toDateTimeString(),
    ]);
});
Route::post("/v1/auth/login", [AuthController::class, "login"]);
Route::post("/v1/auth/biometric-register", [RegistrationController::class, "register"]);
Route::middleware("auth:sanctum")->group(function () {
    Route::post("/v1/auth/logout", [AuthController::class, "logout"]);
    Route::get("/v1/search", ["App\Http\Controllers\Api\SearchController", "search"]);
});
