<?php

namespace App\Http\Controllers;

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
            case EventType::EPISODE_DOWNLOADED:
                return $this->processDownload($request->all());
            default:
                Log::info("Unknown event type found: {$request->type}");
                return response()->json(['message' => 'Unknown event type found.'], 422);
        }
    }

    // For later:
    // We might want to make this a job/event instead of handling it directly in the controller.
    // I.E. acknowledge the event type in 'index' and then queue a job to process the download.
    protected function processDownload(array $data)
    {
        if (Download::where('event_id', $data['event_id'])->exists()) {
            return response()->json(['message' => 'Download already exists.'], 422);
        }

        Download::create([
            'event_id' => $data['event_id'],
            'podcast_id' => $data['podcast_id'],
            'episode_id' => $data['episode_id'],
            'occurred_at' => $data['occurred_at'],
        ]);

        return response()->json(['message' => 'Successfully processed download.'], 200);
    }

    public function stats(Request $request)
    {
        return response()->json(['message' => 'Stats!'], 200);
    }
}
