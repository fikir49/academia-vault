<?php

use Illuminate\Support\Facades\Route;

// This is the "Pulse" of Academia Vault
Route::get('/v1/status', function () {
    return response()->json([
        'app_name' => 'Academia Vault',
        'status' => 'Active',
        'protocol' => 'Decentralized Mesh v1.0',
        'server_time' => now()->toDateTimeString(),
    ]);
}
);
use App\Http\Controllers\Api\SearchController;

Route::get('/v1/search', [SearchController::class, 'search']);