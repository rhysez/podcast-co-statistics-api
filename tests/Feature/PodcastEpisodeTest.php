<?php

use Illuminate\Support\Str;

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
