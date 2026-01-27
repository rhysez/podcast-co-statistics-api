<?php

namespace App\Services;

use App\Models\Download;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DownloadTimeSeriesService
{
    private function aggregate($query, $startDate, $endDate)
    {
        $downloadStats = $query
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE(occurred_at) as date'),
                DB::raw('COUNT(*) as download_count')
            ])
            ->groupBy('date')
            ->orderBy('occurred_at', 'ASC')
            ->get();

        $downloadCountsByDate = $downloadStats->pluck('download_count', 'date');
        $tsData = [];
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $defaultValueForNoDownloads = 0;

        foreach ($period as $date) {
            $dbDate = $date->format('Y-m-d');
            $displayDate = $date->format('d-m-Y');
            $tsData[] = [
                'date'  => $displayDate,
                'download_count' => $downloadCountsByDate->get($dbDate, $defaultValueForNoDownloads),
            ];
        }

        return $tsData;
    }

    public function aggregateByEpisode(string $episodeId, $startDate, $endDate): array
    {
        return $this->aggregate(
            Download::where('episode_id', $episodeId),
            $startDate,
            $endDate
        );
    }
}
