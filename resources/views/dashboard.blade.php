@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    function dashboardStatusLabel(string $eventType): string {
        return match ($eventType) {
            'violation_started' => 'Abierto',
            'violation_resolved' => 'Resuelto',
            default => ucfirst($eventType),
        };
    }

    function dashboardStatusClass(string $eventType): string {
        return match ($eventType) {
            'violation_started' => 'warning',
            'violation_resolved' => 'success',
            default => 'warning',
        };
    }

    function dashboardScenarioLabel(string $scenarioId): string {
        return match ($scenarioId) {
            'helmet_required' => 'Casco obligatorio',
            'vest_required' => 'Chaleco obligatorio',
            'helmet_and_vest_required' => 'Casco y chaleco',
            default => $scenarioId,
        };
    }

    function dashboardViolationLabels(array $violations, string $eventType): array {
        if ($eventType === 'violation_resolved') {
            return ['Resuelto'];
        }

        if (empty($violations)) {
            return ['Sin violaciones'];
        }

        return array_map(function ($item) {
            return match ($item) {
                'missing_helmet' => 'Sin casco',
                'missing_vest' => 'Sin chaleco',
                default => $item,
            };
        }, $violations);
    }

    function dashboardCameraLabel(string $cameraId): string {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
    }
@endphp

@section('content')
<div class="card">
    <form method="GET" action="{{ route('dashboard') }}" class="filters-inline">
        <div class="inline-field">
            <label class="field-label">Fecha desde</label>
            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
        </div>

        <div class="inline-field">
            <label class="field-label">Fecha hasta</label>
            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
        </div>

        <button type="submit" class="btn btn-primary">Aplicar filtro</button>
    </form>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"></div>
        <div class="kpi-value">{{ number_format($totalEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Total eventos registrados</div>
        {{-- <div class="kpi-variation neutral">Rango seleccionado</div> --}}
    </div>

    {{-- <div class="kpi-card">
        <div class="kpi-icon red"></div>
        <div class="kpi-value">{{ number_format($startedViolations, 0, ',', '.') }}</div>
        <div class="kpi-label">Infracciones Iniciadas</div>
        <div class="kpi-variation neutral">event_type = violation_started</div>
    </div> --}}

    {{-- <div class="kpi-card">
        <div class="kpi-icon green"></div>
        <div class="kpi-value">{{ number_format($resolvedViolations, 0, ',', '.') }}</div>
        <div class="kpi-label">Infracciones Resueltas</div>
        <div class="kpi-variation neutral">event_type = violation_resolved</div>
    </div> --}}

    {{-- <div class="kpi-card">
        <div class="kpi-icon orange"></div>
        <div class="kpi-value">{{ number_format($openViolations, 0, ',', '.') }}</div>
        <div class="kpi-label">Eventos Abiertos</div>
        <div class="kpi-variation neutral">Sin resolver</div>
    </div> --}}
</div>

<div class="card">
    <div class="card-header">
        <h2>Últimos Eventos</h2>
        <a href="{{ route('events.index') }}" class="link-primary">Ver todos los eventos</a>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Evento</th>
                    <th>Fecha y hora</th>
                    <th>Cámara</th>
                    <th>Escenario</th>
                    <th>Violaciones</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latestEvents as $event)
                    <tr>
                        <td>
                            <a href="{{ route('events.show', $event->event_id) }}"
                            title="{{ $event->event_id }}">
                                {{ Str::limit($event->event_id, 28) }}
                            </a>
                        </td>
                        <td>
                            {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}
                        </td>
                        <td>{{ dashboardCameraLabel($event->camera_id) }}</td>
                        <td>{{ dashboardScenarioLabel($event->scenario_id) }}</td>
                        <td>
                            <div class="badge-group">
                                @foreach(dashboardViolationLabels($event->violation_codes_json ?? [], $event->event_type) as $violation)
                                    <span class="badge {{ in_array($violation, ['Sin violaciones', 'Resuelto']) ? 'success' : 'danger' }}">
                                        {{ $violation }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ dashboardStatusClass($event->event_type) }}">
                                {{ dashboardStatusLabel($event->event_type) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                No hay eventos en el rango seleccionado.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection