@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    function openStatusLabel($type) {
        return $type === 'violation_started' ? 'Abierto' : 'Resuelto';
    }

    function openStatusClass($type) {
        return $type === 'violation_started' ? 'warning' : 'success';
    }

    function openCamera($c) {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $c));
    }

    function openScenario($s) {
        return match($s) {
            'helmet_required' => 'Casco obligatorio',
            'vest_required' => 'Chaleco obligatorio',
            'helmet_and_vest_required' => 'Casco y chaleco',
            default => $s
        };
    }

    function openViolations($arr) {
        if (empty($arr)) return ['Sin violaciones'];

        return array_map(fn($v) => match($v) {
            'missing_helmet' => 'Sin casco',
            'missing_vest' => 'Sin chaleco',
            default => $v
        }, $arr);
    }

    function openElapsed($date) {
        if (!$date) return 'N/D';

        $seconds = Carbon::parse($date)->diffInSeconds(now());

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
@endphp

<div>
    <div class="card">
        <div class="card-header-column">
            <h2>Eventos Abiertos</h2>
            <p>Infracciones activas que requieren atención</p>
        </div>

        <div class="alert-box">
            ⚠️ Tienes {{ number_format($openCount, 0, ',', '.') }} eventos abiertos
        </div>

        <div class="filters-inline">
            <div class="inline-field">
                <label class="field-label">Cámara</label>
                <select wire:model.live="camera" class="form-control">
                    <option value="all">Todas</option>
                    @foreach($cameras as $camera)
                        <option value="{{ $camera }}">{{ openCamera($camera) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="inline-field">
                <label class="field-label">Escenario</label>
                <select wire:model.live="scenario" class="form-control">
                    <option value="all">Todos</option>
                    @foreach($scenarios as $scenario)
                        <option value="{{ $scenario }}">{{ openScenario($scenario) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="inline-field open-search-field">
                <label class="field-label">Buscar</label>
                <input type="text"
                       wire:model.live.debounce.500ms="search"
                       class="form-control"
                       placeholder="Buscar por ID...">
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-left">
                Mostrando {{ $events->count() }} de {{ $events->total() }} eventos abiertos
            </div>
            <div></div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>
                            <button type="button" wire:click="sortBy('event_id')" class="sort-button">
                                <span>ID</span>
                                @if($sortField === 'event_id')
                                    <span class="sort-indicator active">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @else
                                    <span class="sort-indicator">↕</span>
                                @endif
                            </button>
                        </th>

                        <th>Cámara</th>
                        <th>Escenario</th>

                        <th>
                            <button type="button" wire:click="sortBy('event_confirmed_at')" class="sort-button">
                                <span>Tiempo activo</span>
                                @if($sortField === 'event_confirmed_at')
                                    <span class="sort-indicator active">{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                @else
                                    <span class="sort-indicator">↕</span>
                                @endif
                            </button>
                        </th>

                        <th>Violaciones</th>
                        <th>Estado</th>
                        <th>Acción</th>
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

                            <td>{{ openCamera($event->camera_id) }}</td>
                            <td>{{ openScenario($event->scenario_id) }}</td>
                            <td>{{ openElapsed($event->event_confirmed_at) }}</td>

                            <td>
                                <div class="badge-group">
                                    @foreach(openViolations($event->violation_codes_json ?? []) as $v)
                                        <span class="badge {{ $v === 'Sin violaciones' ? 'success' : 'danger' }}">
                                            {{ $v }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td>
                                <span class="badge {{ openStatusClass($event->event_type) }}">
                                    {{ openStatusLabel($event->event_type) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('events.show', $event->event_id) }}" class="link-primary">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    No hay eventos abiertos para los filtros seleccionados.
                                </div>
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