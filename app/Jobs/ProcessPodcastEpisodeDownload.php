<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPodcastEpisodeDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $payload
    ) {}

    public function handle(): void
    {
        if (Download::where('event_id', $this->payload['event_id'])->exists()) {
            return;
        }

        Download::create([
            'event_id'    => $this->payload['event_id'],
            'podcast_id'  => $this->payload['data']['podcast_id'],
            'episode_id'  => $this->payload['data']['episode_id'],
            'occurred_at' => $this->payload['occurred_at'],
        ]);
    }
}
