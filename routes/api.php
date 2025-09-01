<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WebRTCController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/messages', [MessageController::class, 'sendMessage']);
        Route::get('/messages/{id}', [MessageController::class, 'getMessage']);
        Route::get('/messages', [MessageController::class, 'getMessages']);
        Route::post('/messages/{id}/upload', [MessageController::class, 'uploadFile']);

        Route::post('/calls/initiate', [WebRTCController::class, 'initiateCall']);
        Route::post('/calls/answer', [WebRTCController::class, 'answerCall']);
        Route::post('/calls/ice-candidate', [WebRTCController::class, 'addIceCandidate']);
    });
});
