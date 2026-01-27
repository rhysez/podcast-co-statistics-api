<?php

use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

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


test('cannot receive time series data with invalid date formats', function () {
    $seededEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';

    $startDate = Carbon::now()->subDays(14)->startOfDay()->format('D-M-Y');
    $endDate = Carbon::now()->addDays(14)->startOfDay()->format('D-M-Y');

    $response = $this->json('GET', "/api/episodes/{$seededEpisodeId}/stats", [
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);

    $response->assertStatus(422);
});

test('can receive time series data with the same start and end date', function () {
    $seededEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';

    $date = Carbon::now()->startOfDay();

    $response = $this->json('GET', "/api/episodes/{$seededEpisodeId}/stats", [
        'start_date' => $date,
        'end_date' => $date
    ]);

    $response->assertStatus(200);
});

test('can receive time series data without start and end date', function () {
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

test('can get empty time series data for an unknown episode', function () {
    $unknownEpisodeId = '00000000-0000-0000-0000-000000000000';

    $response = $this->json('GET', "/api/episodes/{$unknownEpisodeId}/stats");

    $response->assertStatus(200);
    $response->assertJson([
        'episode_id' => $unknownEpisodeId,
        'data' => []
    ]);
});





