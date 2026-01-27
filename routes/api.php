<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [WebhookController::class, 'webhookHandler']);
Route::get('/episodes/{id}/stats', [DownloadController::class, 'stats']);
