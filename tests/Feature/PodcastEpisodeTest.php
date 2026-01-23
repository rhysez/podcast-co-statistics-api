<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('webhook stores data successfully', function () {
    $payload = [
        'type' => 'episode.downloaded',
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'episode_id' => Str::uuid()->toString(),
            'podcast_id' => Str::uuid()->toString()
        ]
    ];

    $response = $this->postJson('/api/webhook', $payload);

    $response->assertStatus(202);
    $this->assertDatabaseCount('downloads', 1);
});

test('webhook returns 422 on invalid event type', function () {
    $payload = [
        'type' => 'episode.unknown_event',
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'episode_id' => Str::uuid()->toString(),
            'podcast_id' => Str::uuid()->toString()
        ]
    ];

    $response = $this->postJson('/api/webhook', $payload);

    $response->assertStatus(422);
    $response->assertJsonFragment(['message' => 'Unknown event type found.']);
});
