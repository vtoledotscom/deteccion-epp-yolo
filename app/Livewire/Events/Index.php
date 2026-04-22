<?php

namespace App\Livewire\Events;

use App\Models\EppEvent;
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
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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

        if ($this->eventType !== 'all') {
            $query->where('event_type', $this->eventType);
        }

        if ($this->status !== 'all') {
            if ($this->status === 'open') {
                $query->where('event_type', 'violation_started')
                    ->whereNull('resolved_by_event_id');
            }

            if ($this->status === 'resolved') {
                $query->where('event_type', 'violation_resolved');
            }
        }

        if ($this->search) {
            $query->where('event_id', 'like', '%' . $this->search . '%');
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