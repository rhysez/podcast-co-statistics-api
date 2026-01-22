<?php

use App\Http\Controllers\PodcastEpisodeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [PodcastEpisodeController::class, 'webhookHandler']);

Route::get('/episodes/{episode_id}/stats', [PodcastEpisodeController::class, 'stats']);
