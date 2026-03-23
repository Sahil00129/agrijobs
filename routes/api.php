<?php

use App\Http\Controllers\Api\AuthController;

Route::middleware(['apikey'])->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('update-user', [AuthController::class, 'updateUser']);
        Route::get('user-details', [AuthController::class, 'userDetails']);
    });
});
