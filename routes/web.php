<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;

Route::get('/csrf-token', function () {
    $token = bin2hex(random_bytes(64));
    session(['csrf_token' => $token]);
    Log::info('CSRF generated: ' . $token);
    return response()->json(['success' => true, 'csrf_token' => $token]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);