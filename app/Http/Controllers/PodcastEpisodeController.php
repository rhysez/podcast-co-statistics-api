<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPodcastEpisodeDownload;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

enum EventType: string {
    case EPISODE_DOWNLOADED = 'episode.downloaded';
}

class PodcastEpisodeController extends Controller
{
    public function webhookHandler(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'event_id' => 'required|uuid',
            'occurred_at' => 'required|date',
            'data.episode_id' => 'required|uuid',
            'data.podcast_id' => 'required|uuid',
        ]);

        switch ($request->type) {
            case EventType::EPISODE_DOWNLOADED->value:
                if (Download::where('event_id', $request->event_id)->exists()) {
                    return response()->json(['message' => 'Episode already downloaded'], 400);
                }
                ProcessPodcastEpisodeDownload::dispatch($request->all());
                return response()->json(['message' => 'Webhook accepted'], 202);
            default:
                Log::info("Unknown event type found: {$request->type}");
                return response()->json(['message' => 'Unknown event type found.'], 422);
        }
    }

    public function stats(Request $request)
    {
        return response()->json(['message' => 'Stats!'], 200);
    }
}
