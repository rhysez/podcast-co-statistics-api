<?php

namespace Database\Factories;

use App\Models\Download;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Download>
 */
class DownloadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Download::class;

    public function definition(): array
    {
        return [
            'event_id' => Str::uuid(),
            'podcast_id' => Str::uuid(),
            'episode_id' => Str::uuid(),
            'occurred_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
