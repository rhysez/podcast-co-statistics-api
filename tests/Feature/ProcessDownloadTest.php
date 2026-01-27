<?php

use App\Jobs\ProcessDownload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);



test('download job creates a download record', function () {
    $payload = [
        'event_id' => Str::uuid()->toString(),
        'occurred_at' => now(),
        'data' => [
            'podcast_id' => Str::uuid()->toString(),
            'episode_id' => Str::uuid()->toString(),
        ],
    ];

    $job = new ProcessDownload($payload);

    $job->handle();

    $this->assertDatabaseHas('downloads', [
        'event_id' => $payload['event_id'],
        'podcast_id' => $payload['data']['podcast_id'],
        'episode_id' => $payload['data']['episode_id'],
    ]);
});
