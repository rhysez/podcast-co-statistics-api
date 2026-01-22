<?php

use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

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
   ]);
});

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
