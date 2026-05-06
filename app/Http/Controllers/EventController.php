<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function show(string $eventId)
    {
        $event = EppEvent::query()
            ->with(['evidence', 'manualValidatedBy'])
            ->where('event_id', $eventId)
            ->firstOrFail();

        ActivityLogger::log(
            'view_event_detail',
            'events',
            'Vista de detalle de evento',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'camera_id' => $event->camera_id,
                'scenario_id' => $event->scenario_id,
            ],
        );

        return view('events.show', [
            'event' => $event,
        ]);
    }
}
