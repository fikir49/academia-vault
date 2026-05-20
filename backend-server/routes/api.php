<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. The "Pulse" check
Route::get('/v1/status', function () {
    return response()->json([
        'app_name' => 'Academia Vault',
        'status' => 'Active',
        'protocol' => 'Decentralized Mesh v1.0',
        'server_time' => now()->toDateTimeString(),
    ]);
});

// 2. The Search Route (Cleaned up and simplified)
Route::get('/v1/search', ['App\Http\Controllers\Api\SearchController', 'search']);