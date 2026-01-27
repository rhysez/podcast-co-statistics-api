<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [EventController::class, 'webhookHandler']);
Route::get('/episodes/{id}/stats', [DownloadController::class, 'stats']);
