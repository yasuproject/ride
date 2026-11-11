<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/app-config', [AppConfigController::class, 'show']);

Route::prefix('auth')->group(function () {
    Route::post('/check-phone', [AuthController::class, 'checkPhone'])
        ->middleware('throttle:20,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('/request-otp', [AuthController::class, 'requestOtp'])
        ->middleware('throttle:10,1');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:20,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:10,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:20,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
