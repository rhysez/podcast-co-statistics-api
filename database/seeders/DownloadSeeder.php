<?php

namespace Database\Seeders;

use App\Models\Download;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DownloadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Download::factory()->count(10)->create();
        $this->command->info("Seeded downloads");
    }
}
