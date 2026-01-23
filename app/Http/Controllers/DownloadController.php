<?php

namespace App\Http\Controllers;

use App\Models\Download;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request, string $episodeId)
    {
        $request->validate([
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = null;
        $endDate = null;

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
        }
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
        }

        $downloadStats = Download::where('episode_id', $episodeId)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('occurred_at', 'asc');
    }
}
