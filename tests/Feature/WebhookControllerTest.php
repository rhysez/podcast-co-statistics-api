<?php

use App\Jobs\ProcessDownload;
use App\Models\Download;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('webhook stores data successfully', function () {
    Queue::fake();

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

    Queue::assertPushed(ProcessDownload::class);
});

test('webhook returns 422 on invalid event type', function () {
    Queue::fake();

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

    Queue::assertNothingPushed();
});

test('webhook returns a 409 if an episode has already been downloaded', function () {
    Queue::fake();

    $payload = [
        'type' => 'episode.downloaded',
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'episode_id' => Str::uuid()->toString(),
            'podcast_id' => Str::uuid()->toString()
        ]
    ];

    $existingDownload = Download::create([
        'event_id' => $payload['event_id'],
        'podcast_id' => $payload['data']['podcast_id'],
        'episode_id' => $payload['data']['episode_id'],
        'occurred_at' => $payload['occurred_at'],
    ]);

    $response = $this->postJson('/api/webhook', $payload);

    $response->assertStatus(409);
    $this->assertDatabaseHas('downloads', [
        'event_id' => $existingDownload->event_id,
        'podcast_id' => $existingDownload->podcast_id,
        'episode_id' => $existingDownload->episode_id,
    ]);

    Queue::assertNothingPushed();
});
