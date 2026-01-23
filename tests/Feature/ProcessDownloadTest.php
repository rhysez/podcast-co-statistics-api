<?php

use App\Jobs\ProcessDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('download job is pushed on a successful webhook request', function () {
   Queue::fake([
       ProcessDownload::class
   ]);

    $payload = [
        'type' => 'episode.downloaded',
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'episode_id' => Str::uuid()->toString(),
            'podcast_id' => Str::uuid()->toString()
        ]
    ];

    $this->postJson('/api/webhook', $payload);

    Queue::assertPushed(ProcessDownload::class);
});


test('download job is not pushed on an erroneous webhook request', function () {
    Queue::fake([
        ProcessDownload::class
    ]);

    $payload = [
        'type' => 'episode.unknown_event',
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now()->toIso8601String(),
        'data' => [
            'episode_id' => Str::uuid()->toString(),
            'podcast_id' => Str::uuid()->toString()
        ]
    ];

    $this->postJson('/api/webhook', $payload);

    Queue::assertNotPushed(ProcessDownload::class);
});
