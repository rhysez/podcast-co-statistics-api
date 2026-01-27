<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDownload;
use App\Models\Download;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// If more event types are required, we can just extend this enum
// And then evaluate the variants in the switch used in webhookHandler
enum EventType: string {
    case EPISODE_DOWNLOADED = 'episode.downloaded';
}

class EventController extends Controller
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
                // This is definitely an edge case, but prevents duplicate download event records.
                if (Download::where('event_id', $request->event_id)->exists()) {
                    return response()->json(['message' => 'A download with this event_id already exists'], 400);
                }
                ProcessDownload::dispatch($request->all());
                return response()->json(['message' => 'Episode download has been queued for processing'], 202);
            default:
                Log::info("Unknown event type found: {$request->type}");
                return response()->json(['message' => 'Unknown event type found.'], 422);
        }
    }
}
