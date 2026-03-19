<?php
use App\Http\Controllers\Api\AuthController;

Route::middleware(['apikey'])->group(function(){
    Route::post('/register',[AuthController::class,'register']);
    Route::post('send-otp',[AuthController::class,'sendOtp']);
    Route::post('/login',[AuthController::class,'login']);

    Route::middleware(['auth:sanctum'])->group(function(){
        Route::post('/logout',[AuthController::class,'logout']);
        Route::get('/profile',function(){
            echo "This is a protected route. You are authenticated."; die;
           
        });
    });

});