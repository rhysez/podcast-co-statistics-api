<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\WebhookEventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [WebhookEventController::class, 'webhookHandler']);
Route::get('/episodes/{id}/stats', [DownloadController::class, 'stats']);
