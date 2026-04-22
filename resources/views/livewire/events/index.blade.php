@php
    use Illuminate\Support\Str;

    function evStatusLabel($type) {
        return $type === 'violation_started' ? 'Abierto' : 'Resuelto';
    }

    function evStatusClass($type) {
        return $type === 'violation_started' ? 'warning' : 'success';
    }

    function evCamera($c) {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $c));
    }

    function evScenario($s) {
        return match($s) {
            'helmet_required' => 'Casco obligatorio',
            'vest_required' => 'Chaleco obligatorio',
            'helmet_and_vest_required' => 'Casco y chaleco',
            default => $s
        };
    }

    function evViolations($arr, $type) {
        if ($type === 'violation_resolved') return ['Resuelto'];
        if (empty($arr)) return ['Sin violaciones'];

        return array_map(fn($v) => match($v) {
            'missing_helmet' => 'Sin casco',
            'missing_vest' => 'Sin chaleco',
            default => $v
        }, $arr);
    }
@endphp

<div>
    <div class="card">
        <div class="filters-grid">
            <input type="date" wire:model.live="dateFrom" class="form-control">
            <input type="date" wire:model.live="dateTo" class="form-control">

            <select wire:model.live="camera" class="form-control">
                <option value="all">Todas las cámaras</option>
                @foreach($cameras as $c)
                    <option value="{{ $c }}">{{ evCamera($c) }}</option>
                @endforeach
            </select>

            <select wire:model.live="scenario" class="form-control">
                <option value="all">Todos los escenarios</option>
                @foreach($scenarios as $s)
                    <option value="{{ $s }}">{{ evScenario($s) }}</option>
                @endforeach
            </select>

            <select wire:model.live="eventType" class="form-control">
                <option value="all">Todos los tipos</option>
                <option value="violation_started">Iniciado</option>
                <option value="violation_resolved">Resuelto</option>
            </select>

            <select wire:model.live="status" class="form-control">
                <option value="all">Todos los estados</option>
                <option value="open">Abiertos</option>
                <option value="resolved">Resueltos</option>
            </select>
        </div>
    </div>

    <div class="toolbar">
        <div class="toolbar-left">
            Mostrando {{ $events->count() }} de {{ $events->total() }} eventos
        </div>

        <div class="toolbar-right">
            <input type="text"
                wire:model.live.debounce.500ms="search"
                placeholder="Buscar por ID..."
                class="form-control search-input">

            <a href="{{ route('events.export.csv', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'camera' => $camera,
                'scenario' => $scenario,
                'event_type' => $eventType,
                'status' => $status,
                'search' => $search,
            ]) }}"
            class="btn btn-secondary">
                Exportar CSV
            </a>

            <a href="{{ route('events.export.pdf', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'camera' => $camera,
                'scenario' => $scenario,
                'event_type' => $eventType,
                'status' => $status,
                'search' => $search,
            ]) }}"
            class="btn btn-primary">
                Exportar PDF
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>
                            <button type="button" wire:click="sortBy('event_id')" class="sort-button">
                                <span>ID</span>

                                @if($sortField === 'event_id')
                                    <span class="sort-indicator active">
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    </span>
                                @else
                                    <span class="sort-indicator">↕</span>
                                @endif
                            </button>
                        </th>

                        <th>
                            <button type="button" wire:click="sortBy('event_confirmed_at')" class="sort-button">
                                <span>Fecha</span>

                                @if($sortField === 'event_confirmed_at')
                                    <span class="sort-indicator active">
                                        {{ $sortDirection === 'asc' ? '▲' : '▼' }}
                                    </span>
                                @else
                                    <span class="sort-indicator">↕</span>
                                @endif
                            </button>
                        </th>

                        <th>Cámara</th>
                        <th>Escenario</th>
                        <th>Violaciones</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td title="{{ $event->event_id }}">
                                <a href="{{ route('events.show', $event->event_id) }}" class="link-primary">
                                    {{ Str::limit($event->event_id, 30) }}
                                </a>
                            </td>

                            <td>
                                {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}
                            </td>

                            <td>{{ evCamera($event->camera_id) }}</td>
                            <td>{{ evScenario($event->scenario_id) }}</td>

                            <td>
                                <div class="badge-group">
                                    @foreach(evViolations($event->violation_codes_json ?? [], $event->event_type) as $v)
                                        <span class="badge {{ in_array($v, ['Resuelto', 'Sin violaciones']) ? 'success' : 'danger' }}">
                                            {{ $v }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td>
                                <span class="badge {{ evStatusClass($event->event_type) }}">
                                    {{ evStatusLabel($event->event_type) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">No hay eventos para los filtros seleccionados.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-16">
            {{ $events->links('vendor.livewire.epp-pagination') }}
        </div>
    </div>
</div>