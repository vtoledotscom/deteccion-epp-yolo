<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Support\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    private const FILTER_MANUAL_STATUSES = [
        'all',
        'pending',
        'correct',
        'false_positive',
    ];

    private const FILTER_DETECTED_STATUSES = [
        'all',
        'compliant',
        'non_compliant',
    ];

    private const DASHBOARD_MANUAL_STATUSES = [
        'correct',
        'false_positive',
    ];

    private const QUICK_FILTERS = [
        'all',
        'pending',
        'validated',
        'false_positive',
        'violations',
        'compliance',
    ];

    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : now()->subDays(7)->startOfDay();

        $dateTo = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        $filters = $this->resolveReviewFilters($request, $dateFrom, $dateTo);

        $baseQuery = EppEvent::query()
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo])
            ->where('event_type', 'violation_started');

        $totalEvents = (clone $baseQuery)->count();

        $nonCompliantEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->count();

        $humanPendingEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->where('human_review_status', 'pending')
            ->count();

        $humanResolvedEvents = (clone $baseQuery)
            ->where('status', 'non_compliant')
            ->where('human_review_status', 'resolved')
            ->count();

        $reviewQuery = $this->buildReviewEventsQuery($filters);
        $reviewMetrics = $this->buildReviewMetrics(clone $reviewQuery);

        $reviewEvents = (clone $reviewQuery)
            ->with(['evidence', 'manualValidatedBy'])
            ->orderByDesc('event_confirmed_at')
            ->paginate(8)
            ->withQueryString();

        return view('dashboard', [
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'totalEvents' => $totalEvents,
            'nonCompliantEvents' => $nonCompliantEvents,
            'humanPendingEvents' => $humanPendingEvents,
            'humanResolvedEvents' => $humanResolvedEvents,
            'reviewEvents' => $reviewEvents,
            'reviewMetrics' => $reviewMetrics,
            'reviewFilters' => $filters,
            'quickFilter' => $filters['quick_filter'],
            'reviewCameras' => EppEvent::query()
                ->whereIn('event_type', ['violation_started', 'compliance_observed'])
                ->select('camera_id')
                ->whereNotNull('camera_id')
                ->distinct()
                ->orderBy('camera_id')
                ->pluck('camera_id'),
            'reviewZones' => EppEvent::query()
                ->whereIn('event_type', ['violation_started', 'compliance_observed'])
                ->select('zone_name')
                ->whereNotNull('zone_name')
                ->where('zone_name', '<>', '')
                ->distinct()
                ->orderBy('zone_name')
                ->pluck('zone_name'),
        ]);
    }

    public function storeReview(Request $request, string $eventId): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('review_detection_events'), 403);

        $validated = $request->validate([
            'manual_status' => ['required', Rule::in(self::DASHBOARD_MANUAL_STATUSES)],
        ]);

        $event = DB::transaction(function () use ($eventId, $request, $validated) {
            $event = EppEvent::query()
                ->where('event_id', $eventId)
                ->whereIn('event_type', ['violation_started', 'compliance_observed'])
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
            'dashboard',
            'Validacion manual de evento desde dashboard',
            'epp_event',
            $event->event_id,
            [
                'display_id' => $event->display_id,
                'manual_status' => $validated['manual_status'],
                'source' => 'dashboard',
            ],
            request: $request,
        );

        return redirect()
            ->route('dashboard', $request->query())
            ->with('status', 'Validación manual registrada correctamente.');
    }

    private function resolveReviewFilters(Request $request, \Carbon\CarbonInterface $dateFrom, \Carbon\CarbonInterface $dateTo): array
    {
        $filters = $request->validate([
            'quick_filter' => ['nullable', Rule::in(self::QUICK_FILTERS)],
            'manual_status' => ['nullable', Rule::in(self::FILTER_MANUAL_STATUSES)],
            'detected_status' => ['nullable', Rule::in(self::FILTER_DETECTED_STATUSES)],
            'camera' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return [
            'quick_filter' => $filters['quick_filter'] ?? 'all',
            'manual_status' => $filters['manual_status'] ?? 'all',
            'detected_status' => $filters['detected_status'] ?? 'all',
            'camera' => $filters['camera'] ?? 'all',
            'zone' => $filters['zone'] ?? 'all',
            'date_from' => $filters['date_from'] ?? $dateFrom->format('Y-m-d'),
            'date_to' => $filters['date_to'] ?? $dateTo->format('Y-m-d'),
        ];
    }

    private function buildReviewEventsQuery(array $filters): Builder
    {
        return EppEvent::query()
            ->whereIn('event_type', ['violation_started', 'compliance_observed'])
            ->when($filters['quick_filter'] === 'pending', fn ($query) => $query->whereNull('manual_status'))
            ->when($filters['quick_filter'] === 'validated', fn ($query) => $query->whereNotNull('manual_status'))
            ->when($filters['quick_filter'] === 'false_positive', fn ($query) => $query->where('manual_status', 'false_positive'))
            ->when($filters['quick_filter'] === 'violations', fn ($query) => $query->where('event_type', 'violation_started'))
            ->when($filters['quick_filter'] === 'compliance', fn ($query) => $query->where('event_type', 'compliance_observed'))
            ->when($filters['manual_status'] === 'pending', fn ($query) => $query->whereNull('manual_status'))
            ->when(
                ! in_array($filters['manual_status'], ['all', 'pending'], true),
                fn ($query) => $query->where('manual_status', $filters['manual_status'])
            )
            ->when($filters['detected_status'] !== 'all', fn ($query) => $query->where('status', $filters['detected_status']))
            ->when($filters['camera'] !== 'all', fn ($query) => $query->where('camera_id', $filters['camera']))
            ->when($filters['zone'] !== 'all', fn ($query) => $query->where('zone_name', $filters['zone']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '<=', $filters['date_to']));
    }

    private function buildReviewMetrics(Builder $query): array
    {
        $totalValidated = (clone $query)
            ->whereNotNull('manual_status')
            ->count();
        $pending = (clone $query)
            ->whereNull('manual_status')
            ->count();
        $correct = (clone $query)
            ->where('manual_status', 'correct')
            ->count();
        $falsePositive = (clone $query)
            ->where('manual_status', 'false_positive')
            ->count();

        return [
            'total_validated' => $totalValidated,
            'pending' => $pending,
            'accuracy' => $totalValidated > 0 ? round(($correct / $totalValidated) * 100, 1) : 0,
            'false_positive_rate' => $totalValidated > 0 ? round(($falsePositive / $totalValidated) * 100, 1) : 0,
        ];
    }
}
