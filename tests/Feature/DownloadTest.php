<?php

use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('can create a download record by injecting valid request data', function () {
   $data = [
       'event_id' => Str::uuid()->toString(),
       'podcast_id' => Str::uuid()->toString(),
       'episode_id' => Str::uuid()->toString(),
       'occurred_at' => now()->toIso8601String()
   ];

   Download::create($data);

    $this->assertDatabaseHas('downloads', [
        'event_id' => $data['event_id'],
        'podcast_id' => $data['podcast_id'],
        'episode_id' => $data['episode_id'],
    ]);
});


