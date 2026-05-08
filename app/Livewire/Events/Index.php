<?php

namespace App\Livewire\Events;

use App\Models\EppEvent;
use App\Support\SearchNormalizer;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $dateFrom;
    public $dateTo;
    public $camera = 'all';
    public $scenario = 'all';
    public $eventType = 'all';
    public $status = 'all';
    public $search = '';

    public $sortField = 'event_confirmed_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'dateFrom',
        'dateTo',
        'camera',
        'scenario',
        'eventType',
        'status',
        'search',
        'sortField',
        'sortDirection',
    ];

    public function mount()
    {
        $this->dateFrom ??= $this->defaultDateFrom();
        $this->dateTo ??= $this->defaultDateTo();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCamera()
    {
        $this->resetPage();
    }

    public function updatingScenario()
    {
        $this->resetPage();
    }

    public function updatingEventType()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function applyFilters()
    {
        $this->resetPage();
    }

    public function clearTopFilters()
    {
        $this->dateFrom = $this->defaultDateFrom();
        $this->dateTo = $this->defaultDateTo();
        $this->camera = 'all';
        $this->scenario = 'all';
        $this->eventType = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    private function defaultDateFrom(): string
    {
        return now()->subDays(7)->format('Y-m-d');
    }

    private function defaultDateTo(): string
    {
        return now()->format('Y-m-d');
    }

    public function render()
    {
        $dateFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dateTo = Carbon::parse($this->dateTo)->endOfDay();

        $query = EppEvent::query()
            ->whereBetween('event_confirmed_at', [$dateFrom, $dateTo]);

        if ($this->camera !== 'all') {
            $query->where('camera_id', $this->camera);
        }

        if ($this->scenario !== 'all') {
            $query->where('scenario_id', $this->scenario);
        }

        if ($this->status === 'open') {
            $query->where('event_type', 'violation_started')
                ->whereNull('resolved_by_event_id');
        } elseif ($this->status === 'resolved') {
            $query->where('event_type', 'violation_resolved');
        } elseif (in_array($this->eventType, ['violation_started', 'violation_resolved'], true)) {
            $query->where('event_type', $this->eventType);
        } else {
            $query->where('event_type', 'violation_started');
        }

        $search = trim((string) $this->search);

        if ($search !== '') {
            $normalizedSearch = SearchNormalizer::normalize($search);
            $sequenceId = SearchNormalizer::eventSequenceIdFromSearch($search);

            $query->where(function ($query) use ($normalizedSearch, $sequenceId) {
                $query->whereRaw('LOWER(event_id) LIKE ?', ['%' . $normalizedSearch . '%']);

                if ($sequenceId !== null) {
                    $query->orWhere('sequence_id', $sequenceId);
                }
            });
        }

        $events = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(config('epp.pagination.events'));

        $cameras = EppEvent::select('camera_id')->distinct()->pluck('camera_id');
        $scenarios = EppEvent::select('scenario_id')->distinct()->pluck('scenario_id');

        return view('livewire.events.index', [
            'events' => $events,
            'cameras' => $cameras,
            'scenarios' => $scenarios,
        ]);
    }
}
