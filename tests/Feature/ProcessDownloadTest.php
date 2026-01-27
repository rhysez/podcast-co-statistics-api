<?php

use App\Jobs\ProcessDownload;
use App\Models\Download;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

test('download job does not create duplicate download for same event_id', function () {
    $eventId = Str::uuid()->toString();

    Download::factory()->create([
        'event_id' => $eventId,
    ]);

    $payload = [
        'event_id' => $eventId,
        'occurred_at' => now(),
        'data' => [
            'podcast_id' => Str::uuid()->toString(),
            'episode_id' => Str::uuid()->toString(),
        ],
    ];

    // I'm not explicitly handling the exception thrown by the job here because duplicates are prevented
    // in the webhook controller, but if this job is dispatched from other areas of the API then I think we
    // may want to handle the duplicates in the job. Open to discussion on this, I think the main issue with
    // handling the exception in the job is that it doesn't provide any response to the user's request.
    $this->expectException(QueryException::class);
    (new ProcessDownload($payload))->handle();
});
