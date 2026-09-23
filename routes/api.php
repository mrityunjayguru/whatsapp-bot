<?php

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\WidgetMessageController;


Route::get('/webhook', [WebhookController::class, 'verifyWebhook']);
Route::post('/webhook', [WebhookController::class, 'receiveWebhook']);

Route::post('/widget/message', [WidgetMessageController::class, 'send']);
Route::post('/widget/history', [WidgetMessageController::class, 'history']);
Route::post('/widget/close', [WidgetMessageController::class, 'close']);
