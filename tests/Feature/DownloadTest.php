<?php

use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('can receive time series data without specifying start and end date', function () {
    // This is the UUID of the episode created by DownloadSeeder
    // In production, we wouldn't do this, this is just for the interview demo
    $seededEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';

    $response = $this->json('GET', "/api/episodes/{$seededEpisodeId}/stats");
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'episode_id',
        'range' => [
            'start',
            'end'
        ],
        'data'
    ]);
});

test('can receive time series data with start and end date', function () {
    $seededEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';

    $startDate = Carbon::now()->subDays(14)->startOfDay();
    $endDate = Carbon::now()->addDays(14)->endOfDay();

    $response = $this->json('GET', "/api/episodes/{$seededEpisodeId}/stats", [
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'episode_id',
        'range' => [
            'start',
            'end'
        ],
        'data'
    ]);
});

test('cannot receive time series data with future start date and past end date', function () {
    $seededEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';

    $startDate = Carbon::now()->addDays(4)->startOfDay();
    $endDate = Carbon::now()->endOfDay();

    $response = $this->json('GET', "/api/episodes/{$seededEpisodeId}/stats", [
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    $response->assertStatus(422);
});




