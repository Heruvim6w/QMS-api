<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\WebRTCController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']); // Step 1: Send confirmation email
    Route::post('/login/confirm', [AuthController::class, 'confirmLogin']); // Step 2: Confirm with token (API)
    Route::get('/login/confirm/{token}', [AuthController::class, 'confirmLoginWeb']); // Step 2: Confirm with token (Web link)

    // Protected routes
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);

        // User profile routes
        Route::get('/users/profile', [UserProfileController::class, 'getProfile']);
        Route::post('/users/username', [UserProfileController::class, 'setUsername']);
        Route::get('/users/search', [UserProfileController::class, 'searchUser']);
        Route::get('/users/{identifier}', [UserProfileController::class, 'getUserByIdentifier']);

        // Language management routes
        Route::get('/languages', [UserProfileController::class, 'getLanguages']);
        Route::put('/users/locale', [UserProfileController::class, 'updateLocale']);

        // Session management routes
        Route::get('/sessions', [UserProfileController::class, 'getSessions']);
        Route::delete('/sessions/{sessionId}', [UserProfileController::class, 'endSession']);

        // Status management routes
        Route::post('/users/status', [UserProfileController::class, 'setStatus']);
        Route::get('/users/status/available', [UserProfileController::class, 'getAvailableStatuses']);
        Route::get('/users/{identifier}/status', [UserProfileController::class, 'getUserStatus']);

        // Chat routes
        Route::get('/chats', [ChatController::class, 'index']);
        Route::post('/chats', [ChatController::class, 'store']);
        Route::get('/chats/{id}', [ChatController::class, 'show']);
        Route::put('/chats/{id}', [ChatController::class, 'update']);
        Route::delete('/chats/{id}', [ChatController::class, 'destroy']);
        Route::post('/chats/{id}/add-user', [ChatController::class, 'addUser']);
        Route::post('/chats/{id}/remove-user/{userId}', [ChatController::class, 'removeUser']);
        Route::post('/chats/{id}/mute', [ChatController::class, 'toggleMute']);
        Route::post('/chats/get-or-create-private/{userId}', [ChatController::class, 'getOrCreatePrivateChat']);
        Route::get('/chats/favorites/get-or-create', [ChatController::class, 'getOrCreateFavoritesChat']);

        // Message routes
        Route::post('/messages', [MessageController::class, 'sendMessage']);
        Route::get('/messages/{id}', [MessageController::class, 'getMessage']);
        Route::get('/messages', [MessageController::class, 'getMessages']);
        Route::post('/messages/{id}/upload', [MessageController::class, 'uploadFile']);

        // Attachment routes
        Route::get('/attachments/{id}', [AttachmentController::class, 'show']);
        Route::get('/attachments/{id}/download', [AttachmentController::class, 'download']);
        Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);
        Route::get('/messages/{messageId}/attachments', [AttachmentController::class, 'getMessageAttachments']);

        // WebRTC routes
        Route::post('/calls/initiate', [WebRTCController::class, 'initiateCall']);
        Route::post('/calls/answer', [WebRTCController::class, 'answerCall']);
        Route::post('/calls/ice-candidate', [WebRTCController::class, 'addIceCandidate']);
        Route::post('/calls/{callUuid}/decline', [WebRTCController::class, 'declineCall']);
        Route::post('/calls/{callUuid}/end', [WebRTCController::class, 'endCall']);
        Route::get('/calls/{callUuid}', [WebRTCController::class, 'getCall']);
    });
});
