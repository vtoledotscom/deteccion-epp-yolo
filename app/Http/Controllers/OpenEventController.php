<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Models\EventAction;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpenEventController extends Controller
{
    private const NOTIFICATION_METHODS = [
        'verbal',
        'email',
        'telefono',
        'supervisor_directo',
        'otro',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'camera' => (string) $request->query('camera', 'all'),
            'scenario' => (string) $request->query('scenario', 'all'),
            'search' => trim((string) $request->query('search', '')),
        ];

        $events = $this->openEventsQuery()
            ->when($filters['camera'] !== 'all', fn ($query) => $query->where('camera_id', $filters['camera']))
            ->when($filters['scenario'] !== 'all', fn ($query) => $query->where('scenario_id', $filters['scenario']))
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('event_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('sequence_id', $filters['search']);
                });
            })
            ->orderByDesc('event_confirmed_at')
            ->paginate(config('epp.pagination.open_events'))
            ->withQueryString();

        ActivityLogger::log(
            'view_open_events',
            'open_events',
            'Vista de eventos abiertos',
            metadata: [
                'filters' => $filters,
            ],
            request: $request,
        );

        return view('events.open.index', [
            'events' => $events,
            'filters' => $filters,
            'cameras' => $this->openEventsQuery()->select('camera_id')->distinct()->orderBy('camera_id')->pluck('camera_id'),
            'scenarios' => $this->openEventsQuery()->select('scenario_id')->distinct()->orderBy('scenario_id')->pluck('scenario_id'),
        ]);
    }

    public function show(string $eventId): View
    {
        $event = EppEvent::query()
            ->with(['evidence', 'actions.user', 'humanResolvedBy'])
            ->where('event_id', $eventId)
            ->firstOrFail();

        ActivityLogger::log(
            'view_open_event_detail',
            'open_events',
            'Vista de detalle de evento abierto',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'human_review_status' => $event->human_review_status,
            ],
        );

        return view('events.open.show', [
            'event' => $event,
            'notificationMethods' => self::NOTIFICATION_METHODS,
        ]);
    }

    public function closed(Request $request): View
    {
        $filters = [
            'camera' => (string) $request->query('camera', 'all'),
            'scenario' => (string) $request->query('scenario', 'all'),
            'search' => trim((string) $request->query('search', '')),
        ];

        $events = $this->closedEventsQuery()
            ->when($filters['camera'] !== 'all', fn ($query) => $query->where('camera_id', $filters['camera']))
            ->when($filters['scenario'] !== 'all', fn ($query) => $query->where('scenario_id', $filters['scenario']))
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->where('event_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('sequence_id', $filters['search']);
                });
            })
            ->orderByDesc('human_resolved_at')
            ->paginate(config('epp.pagination.open_events'))
            ->withQueryString();

        ActivityLogger::log(
            'view_closed_events',
            'open_events',
            'Vista de eventos cerrados',
            metadata: [
                'filters' => $filters,
            ],
            request: $request,
        );

        return view('events.closed.index', [
            'events' => $events,
            'filters' => $filters,
            'cameras' => $this->closedEventsQuery()->select('camera_id')->distinct()->orderBy('camera_id')->pluck('camera_id'),
            'scenarios' => $this->closedEventsQuery()->select('scenario_id')->distinct()->orderBy('scenario_id')->pluck('scenario_id'),
        ]);
    }

    public function closedShow(string $eventId): View
    {
        $event = $this->closedEventsQuery()
            ->with(['actions.user', 'humanResolvedBy'])
            ->where('event_id', $eventId)
            ->firstOrFail();

        ActivityLogger::log(
            'view_closed_event_detail',
            'open_events',
            'Vista de detalle de evento cerrado',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'human_review_status' => $event->human_review_status,
            ],
        );

        return view('events.closed.show', [
            'event' => $event,
        ]);
    }

    public function resolve(Request $request, string $eventId): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('resolve_open_events'), 403, 'No tienes permisos para cerrar eventos abiertos.');

        $validated = $request->validate([
            'notified_person' => ['required', 'string', 'max:255'],
            'notification_method' => ['required', Rule::in(self::NOTIFICATION_METHODS)],
            'resolution_note' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $event = DB::transaction(function () use ($eventId, $request, $validated) {
            $event = EppEvent::query()
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->human_review_status === 'resolved') {
                throw ValidationException::withMessages([
                    'event' => 'Este evento ya fue cerrado.',
                ]);
            }

            if ($event->status !== 'non_compliant') {
                throw ValidationException::withMessages([
                    'event' => 'Solo se pueden cerrar eventos no conformes.',
                ]);
            }

            $event->forceFill([
                'human_review_status' => 'resolved',
                'human_resolved_by' => $request->user()->id,
                'human_resolved_at' => now(),
                'human_notified_person' => $validated['notified_person'],
                'human_notification_method' => $validated['notification_method'],
                'human_resolution_note' => $validated['resolution_note'],
            ])->save();

            EventAction::create([
                'event_id' => $event->event_id,
                'user_id' => $request->user()->id,
                'action' => 'resolved',
                'note' => $validated['resolution_note'],
                'notified_person' => $validated['notified_person'],
                'notification_method' => $validated['notification_method'],
            ]);

            return $event;
        });

        ActivityLogger::log(
            'resolve_open_event',
            'open_events',
            'Evento abierto notificado y cerrado',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'notified_person' => $validated['notified_person'],
                'notification_method' => $validated['notification_method'],
            ],
            request: $request,
        );

        return redirect()
            ->route('events.open.show', $event->event_id)
            ->with('status', 'Evento notificado y cerrado correctamente.');
    }

    public function comment(Request $request, string $eventId): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('resolve_open_events'), 403, 'No tienes permisos para comentar eventos abiertos.');

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $event = EppEvent::query()
            ->where('event_id', $eventId)
            ->firstOrFail();

        EventAction::create([
            'event_id' => $event->event_id,
            'user_id' => $request->user()->id,
            'action' => 'commented',
            'note' => $validated['note'],
        ]);

        ActivityLogger::log(
            'comment_open_event',
            'open_events',
            'Comentario en evento abierto',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
            ],
            request: $request,
        );

        return back()->with('status', 'Comentario registrado correctamente.');
    }

    private function openEventsQuery()
    {
        return EppEvent::query()
            ->with('evidence')
            ->where('human_review_status', 'pending')
            ->where('status', 'non_compliant');
    }

    private function closedEventsQuery()
    {
        return EppEvent::query()
            ->with(['evidence', 'humanResolvedBy'])
            ->where('human_review_status', 'resolved')
            ->where('status', 'non_compliant');
    }
}
