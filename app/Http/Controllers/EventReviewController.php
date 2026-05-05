<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventReviewController extends Controller
{
    private const FILTER_MANUAL_STATUSES = [
        'all',
        'pending',
        'correct',
        'incorrect',
        'false_positive',
        'not_evaluable',
    ];

    private const FILTER_DETECTED_STATUSES = [
        'all',
        'compliant',
        'non_compliant',
    ];

    private const MANUAL_STATUSES = [
        'correct',
        'incorrect',
        'false_positive',
        'not_evaluable',
    ];

    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('review_detection_events'), 403);

        $filters = $request->validate([
            'manual_status' => ['nullable', Rule::in(self::FILTER_MANUAL_STATUSES)],
            'detected_status' => ['nullable', Rule::in(self::FILTER_DETECTED_STATUSES)],
            'camera' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $filters = [
            'manual_status' => $filters['manual_status'] ?? 'all',
            'detected_status' => $filters['detected_status'] ?? 'all',
            'camera' => $filters['camera'] ?? 'all',
            'zone' => $filters['zone'] ?? 'all',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        $query = EppEvent::query()
            ->with(['evidence', 'manualValidatedBy'])
            ->when($filters['manual_status'] === 'pending', fn ($query) => $query->whereNull('manual_status'))
            ->when(
                ! in_array($filters['manual_status'], ['all', 'pending'], true),
                fn ($query) => $query->where('manual_status', $filters['manual_status'])
            )
            ->when($filters['detected_status'] !== 'all', fn ($query) => $query->where('status', $filters['detected_status']))
            ->when($filters['camera'] !== 'all', fn ($query) => $query->where('camera_id', $filters['camera']))
            ->when($filters['zone'] !== 'all', fn ($query) => $query->where('zone_name', $filters['zone']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '<=', $filters['date_to']))
            ->orderByDesc('event_confirmed_at');

        $events = $query
            ->paginate(12)
            ->withQueryString();

        ActivityLogger::log(
            'view_event_review',
            'events',
            'Vista de validacion manual de eventos',
            metadata: [
                'page' => $request->query('page', 1),
                'filters' => $filters,
            ],
            request: $request,
        );

        return view('events.review', [
            'events' => $events,
            'filters' => $filters,
            'cameras' => EppEvent::query()
                ->select('camera_id')
                ->whereNotNull('camera_id')
                ->distinct()
                ->orderBy('camera_id')
                ->pluck('camera_id'),
            'zones' => EppEvent::query()
                ->select('zone_name')
                ->whereNotNull('zone_name')
                ->where('zone_name', '<>', '')
                ->distinct()
                ->orderBy('zone_name')
                ->pluck('zone_name'),
        ]);
    }

    public function store(Request $request, string $eventId): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('review_detection_events'), 403);

        $validated = $request->validate([
            'manual_status' => ['required', Rule::in(self::MANUAL_STATUSES)],
        ]);

        $event = DB::transaction(function () use ($eventId, $request, $validated) {
            $event = EppEvent::query()
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->firstOrFail();

            $event->forceFill([
                'manual_status' => $validated['manual_status'],
                'manual_validated_at' => now(),
                'manual_validated_by' => $request->user()->id,
            ])->save();

            return $event;
        });

        ActivityLogger::log(
            'manual_review_event',
            'events',
            'Validacion manual de evento',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'manual_status' => $validated['manual_status'],
            ],
            request: $request,
        );

        return redirect()
            ->route('events.review', $request->query())
            ->with('status', 'Validacion manual registrada correctamente.');
    }
}
