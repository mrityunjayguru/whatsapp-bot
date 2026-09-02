<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;


Route::get('/webhook', [WebhookController::class, 'verifyWebhook']);
Route::post('/webhook', [WebhookController::class, 'receiveWebhook']);

// Route::get('/webhook/allwebhooks', [WebhookController::class, 'getAllWebhooks']);
// Route::post('/webhook/update', [WebhookController::class, 'updateContact']);
// Route::post('/webhook/events', [WebhookController::class, 'events']);