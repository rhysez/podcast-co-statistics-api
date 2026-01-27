<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Services\DownloadTimeSeriesService;
use App\Services\TimeSeriesStatsService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DownloadController extends Controller
{
    public function stats(Request $request, string $episodeId)
    {
        $request->validate([
            'start_date' => [
                'bail',
                'nullable',
                'date',
                'required_with:end_date',
                'before_or_equal:end_date'
            ],
            'end_date' => [
                'nullable',
                'date',
                'required_with:start_date',
                'after_or_equal:start_date'
            ],
        ]);

        // If the client doesn't provide start_date, we start from 7 days ago
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
        } else {
            $startDate = Carbon::now()->subDays(7)->startOfDay();
        }

        // And if client doesn't provide end_date, we end on today
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        } else {
            $endDate = Carbon::now()->endOfDay();
        }

        $statsService = new DownloadTimeSeriesService();
        $tsData = $statsService->aggregateByEpisode($episodeId, $startDate, $endDate);

        return response()->json([
            'episode_id' => $episodeId,
            'range' => [
                'start' => $startDate?->toIso8601String(),
                'end'   => $endDate?->toIso8601String(),
            ],
            'data' => $tsData
        ]);
    }
}
