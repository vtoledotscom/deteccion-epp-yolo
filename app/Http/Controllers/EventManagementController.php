<?php

namespace App\Http\Controllers;

use App\Models\EppEvent;
use App\Support\ActivityLogger;
use App\Support\SearchNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventManagementController extends Controller
{
    private const TABS = [
        'pending',
        'closed',
        'all',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(self::TABS)],
            'camera' => ['nullable', 'string', 'max:255'],
            'scenario' => ['nullable', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $filters = [
            'tab' => $filters['tab'] ?? 'pending',
            'camera' => $filters['camera'] ?? 'all',
            'scenario' => $filters['scenario'] ?? 'all',
            'zone' => $filters['zone'] ?? 'all',
            'search' => trim($filters['search'] ?? ''),
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        $query = $this->buildQuery($filters);

        $events = (clone $query)
            ->with(['evidence', 'humanResolvedBy'])
            ->orderByRaw('human_review_status = ? DESC', ['pending'])
            ->orderByDesc('event_confirmed_at')
            ->paginate(config('epp.pagination.open_events'))
            ->withQueryString();

        ActivityLogger::log(
            'view_event_management',
            'open_events',
            'Vista unificada de gestion operacional de eventos',
            metadata: [
                'filters' => $filters,
            ],
            request: $request,
        );

        $baseOptionsQuery = EppEvent::query()
            ->where('status', 'non_compliant')
            ->whereIn('human_review_status', ['pending', 'resolved']);

        return view('events.management.index', [
            'events' => $events,
            'filters' => $filters,
            'cameras' => (clone $baseOptionsQuery)
                ->select('camera_id')
                ->whereNotNull('camera_id')
                ->distinct()
                ->orderBy('camera_id')
                ->pluck('camera_id'),
            'scenarios' => (clone $baseOptionsQuery)
                ->select('scenario_id')
                ->whereNotNull('scenario_id')
                ->distinct()
                ->orderBy('scenario_id')
                ->pluck('scenario_id'),
            'zones' => (clone $baseOptionsQuery)
                ->select('zone_name')
                ->whereNotNull('zone_name')
                ->where('zone_name', '<>', '')
                ->distinct()
                ->orderBy('zone_name')
                ->pluck('zone_name'),
        ]);
    }

    private function buildQuery(array $filters): Builder
    {
        $matchingManagementStatuses = SearchNormalizer::matchingEventManagementStatuses($filters['search']);
        $matchingDetectedStatuses = SearchNormalizer::matchingEventDetectedStatuses($filters['search']);
        $matchingManualStatuses = SearchNormalizer::matchingEventManualStatuses($filters['search']);
        $displaySequenceId = $this->sequenceIdFromDisplaySearch($filters['search']);

        return EppEvent::query()
            ->when(
                $matchingDetectedStatuses === [],
                fn ($query) => $query->where('status', 'non_compliant')
            )
            ->when($filters['tab'] === 'pending', fn ($query) => $query->where('human_review_status', 'pending'))
            ->when($filters['tab'] === 'closed', fn ($query) => $query->where('human_review_status', 'resolved'))
            ->when($filters['tab'] === 'all', fn ($query) => $query->whereIn('human_review_status', ['pending', 'resolved']))
            ->when($filters['camera'] !== 'all', fn ($query) => $query->where('camera_id', $filters['camera']))
            ->when($filters['scenario'] !== 'all', fn ($query) => $query->where('scenario_id', $filters['scenario']))
            ->when($filters['zone'] !== 'all', fn ($query) => $query->where('zone_name', $filters['zone']))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('event_confirmed_at', '<=', $filters['date_to']))
            ->when($filters['search'] !== '', function ($query) use (
                $filters,
                $matchingManagementStatuses,
                $matchingDetectedStatuses,
                $matchingManualStatuses,
                $displaySequenceId
            ) {
                $query->where(function ($query) use (
                    $filters,
                    $matchingManagementStatuses,
                    $matchingDetectedStatuses,
                    $matchingManualStatuses,
                    $displaySequenceId
                ) {
                    $query->where('event_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('sequence_id', $filters['search'])
                        ->orWhere('camera_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('scenario_id', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('zone_name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('status', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('human_review_status', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('manual_status', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('violation_codes_json', 'like', '%' . $filters['search'] . '%');

                    if ($displaySequenceId !== null) {
                        $query->orWhere('sequence_id', $displaySequenceId);
                    }

                    if ($matchingManagementStatuses !== []) {
                        $query->orWhereIn('human_review_status', $matchingManagementStatuses);
                    }

                    if ($matchingDetectedStatuses !== []) {
                        $query->orWhereIn('status', $matchingDetectedStatuses);
                    }

                    if ($matchingManualStatuses !== []) {
                        $query->orWhereIn('manual_status', $matchingManualStatuses);
                    }
                });
            });
    }

    private function sequenceIdFromDisplaySearch(string $search): ?int
    {
        $normalized = SearchNormalizer::normalize($search);

        if (! preg_match('/^evt-?0*(\d+)$/', $normalized, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
