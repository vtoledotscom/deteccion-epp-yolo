<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function show(string $eventId)
    {
        $event = EppEvent::query()
            ->with('evidence')
            ->where('event_id', $eventId)
            ->firstOrFail();

        return view('events.show', [
            'event' => $event,
        ]);
    }
}