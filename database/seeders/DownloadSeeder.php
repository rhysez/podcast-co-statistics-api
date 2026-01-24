<?php

namespace Database\Seeders;

use App\Models\Download;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // I wanted to create a hard-coded episode ID so we can track one episode over time during the interview
        // Ideally, we'd have a PodcastEpisode model which represents an episode and can be related to the Download model
        $targetEpisodeId = '88a0e4c0-0000-41d4-a716-446655440000';
        $podcastId = Str::uuid();
        $numberOfDaysToSimulate = 14;
        $downloadMultiplier = 2;
        $numberOfNoiseDownloads = 50;

        $this->command->info("Seeding data for episode: {$targetEpisodeId}");

        for ($i = $numberOfDaysToSimulate; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);

            // Creates somewhat of a variance in the number of downloads for each iteration
            $downloadCountForThisDay = rand(10, 20) + ($numberOfDaysToSimulate - $i) * $downloadMultiplier;

            Download::factory()
                ->count($downloadCountForThisDay)
                ->create([
                    'episode_id' => $targetEpisodeId,
                    'podcast_id' => $podcastId,
                    'occurred_at' => $date->copy()->setHour(rand(9, 17)), // Business hours
                ]);
        }

        // Adding a load of random download records to create noise.
        Download::factory()->count($numberOfNoiseDownloads)->create();

        $this->command->info("Seeded 14 days of download data");
    }
}
