<?php

namespace App\Livewire\Events;

use App\Models\EppEvent;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Open extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $camera = 'all';
    public $scenario = 'all';
    public $search = '';

    public $sortField = 'event_confirmed_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'camera',
        'scenario',
        'search',
        'sortField',
        'sortDirection',
    ];

    public function updatingCamera()
    {
        $this->resetPage();
    }

    public function updatingScenario()
    {
        $this->resetPage();
    }

    public function updatingSearch()
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
        $query = EppEvent::query()
            ->where('event_type', 'violation_started')
            ->whereNull('resolved_by_event_id');

        if ($this->camera !== 'all') {
            $query->where('camera_id', $this->camera);
        }

        if ($this->scenario !== 'all') {
            $query->where('scenario_id', $this->scenario);
        }

        if ($this->search) {
            $query->where('event_id', 'like', '%' . $this->search . '%');
        }

        $events = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(config('epp.pagination.open_events'));

        $cameras = EppEvent::query()
            ->where('event_type', 'violation_started')
            ->whereNull('resolved_by_event_id')
            ->select('camera_id')
            ->distinct()
            ->pluck('camera_id');

        $scenarios = EppEvent::query()
            ->where('event_type', 'violation_started')
            ->whereNull('resolved_by_event_id')
            ->select('scenario_id')
            ->distinct()
            ->pluck('scenario_id');

        $openCount = EppEvent::query()
            ->where('event_type', 'violation_started')
            ->whereNull('resolved_by_event_id')
            ->count();

        return view('livewire.events.open', [
            'events' => $events,
            'cameras' => $cameras,
            'scenarios' => $scenarios,
            'openCount' => $openCount,
        ]);
    }
}