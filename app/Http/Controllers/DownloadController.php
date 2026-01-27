<?php

namespace App\Http\Controllers;

use App\Services\DownloadTimeSeriesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function statsForEpisode(Request $request, string $episodeId): JsonResponse
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

        $statsService = new DownloadTimeSeriesService();

        $dates = $statsService->dateRange($request);

        $startDate = $dates['start_date'];
        $endDate = $dates['end_date'];

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
