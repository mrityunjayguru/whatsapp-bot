<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\WidgetMessageController;
use Illuminate\Support\Facades\Route;

// Authentication APIs (Unified Company & Employee Login)
Route::post('/verify-company', [AuthController::class, 'verifyCompany']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Conversations API
    Route::get('/conversations', [\App\Http\Controllers\Api\ConversationApiController::class, 'index']);
    Route::get('/conversations/{id}', [\App\Http\Controllers\Api\ConversationApiController::class, 'show']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\Api\ConversationApiController::class, 'sendMessage']);
    Route::post('/conversations/{id}/status', [\App\Http\Controllers\Api\ConversationApiController::class, 'changeStatus']);

    // Contacts API (Edit Contact & Tags)
    Route::get('/contacts/{id}', [\App\Http\Controllers\Api\ContactApiController::class, 'show']);
    Route::match(['post', 'put'], '/contacts/{id}', [\App\Http\Controllers\Api\ContactApiController::class, 'update']);
    Route::get('/tags', [\App\Http\Controllers\Api\ContactApiController::class, 'getTags']);
    Route::get('/contacts/{id}/tags', [\App\Http\Controllers\Api\ContactApiController::class, 'getContactTags']);
    Route::post('/contacts/{id}/tags', [\App\Http\Controllers\Api\ContactApiController::class, 'updateTags']);
});

Route::get('/webhook', [WebhookController::class, 'verifyWebhook']);
Route::post('/webhook', [WebhookController::class, 'receiveWebhook']);

Route::post('/widget/message', [WidgetMessageController::class, 'send']);
Route::post('/widget/select', [WidgetMessageController::class, 'select']);
Route::post('/widget/history', [WidgetMessageController::class, 'history']);
Route::post('/widget/close', [WidgetMessageController::class, 'close']);
Route::get('/widget/download', [WidgetMessageController::class, 'download'])->name('widget.download');

