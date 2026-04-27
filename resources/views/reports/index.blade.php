@extends('layouts.app')

@php
    function reportScenarioLabel(string $scenarioId): string {
        return match ($scenarioId) {
            'helmet_required' => 'Casco obligatorio',
            'vest_required' => 'Chaleco obligatorio',
            'helmet_and_vest_required' => 'Casco y chaleco',
            default => $scenarioId,
        };
    }

    function reportCameraLabel(string $cameraId): string {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
    }

    function reportEventTypeLabel(string $eventType): string {
        return match ($eventType) {
            'violation_started' => 'Iniciado',
            'violation_resolved' => 'Resuelto',
            default => $eventType,
        };
    }

    function reportViolationLabels(array $violations, string $eventType): array {
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
@endphp

@section('content')
<div class="card">
    <div class="card-header-column">
        <h2>Reportes</h2>
        <p>Genera informes y análisis de eventos</p>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="filters-grid">
        <div>
            <label class="field-label">Fecha desde</label>
            <input class="form-control" type="date" name="date_from" value="{{ $dateFrom }}">
        </div>

        <div>
            <label class="field-label">Fecha hasta</label>
            <input class="form-control" type="date" name="date_to" value="{{ $dateTo }}">
        </div>

        <div>
            <label class="field-label">Cámara</label>
            <select class="form-control" name="camera">
                <option value="all">Todas las cámaras</option>
                @foreach($cameras as $camera)
                    <option value="{{ $camera }}" @selected($filters['camera'] === $camera)>
                        {{ reportCameraLabel($camera) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label">Escenario</label>
            <select class="form-control" name="scenario">
                <option value="all">Todos los escenarios</option>
                @foreach($scenarios as $scenario)
                    <option value="{{ $scenario }}" @selected($filters['scenario'] === $scenario)>
                        {{ reportScenarioLabel($scenario) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="field-label">Tipo de evento</label>
            <select class="form-control" name="event_type">
                <option value="all" @selected($filters['event_type'] === 'all')>Todos</option>
                <option value="violation_started" @selected($filters['event_type'] === 'violation_started')>Iniciado</option>
                <option value="violation_resolved" @selected($filters['event_type'] === 'violation_resolved')>Resuelto</option>
            </select>
        </div>

        <div class="report-actions-cell">
            <button class="btn btn-primary" type="submit">Generar Reporte</button>
        </div>
    </form>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon blue"></div>
        <div class="kpi-value">{{ number_format($summary['total_events'], 0, ',', '.') }}</div>
        <div class="kpi-label">Total Eventos</div>
    </div>

    {{-- <div class="kpi-card">
        <div class="kpi-icon red"></div>
        <div class="kpi-value">{{ number_format($summary['started_violations'], 0, ',', '.') }}</div>
        <div class="kpi-label">Infracciones Iniciadas</div>
    </div> --}}

    {{-- <div class="kpi-card">
        <div class="kpi-icon green"></div>
        <div class="kpi-value">{{ number_format($summary['resolved_violations'], 0, ',', '.') }}</div>
        <div class="kpi-label">Infracciones Resueltas</div>
    </div> --}}

    {{-- <div class="kpi-card">
        <div class="kpi-icon orange"></div>
        <div class="kpi-value">{{ number_format($summary['open_violations'], 0, ',', '.') }}</div>
        <div class="kpi-label">Eventos Abiertos</div>
    </div> --}}
</div>

<div class="report-summary-grid">
    <div class="card">
        <div class="card-header-column">
            <h3>Eventos por Escenario</h3>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Escenario</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scenarioSummary as $row)
                        <tr>
                            <td>{{ reportScenarioLabel($row->scenario_id) }}</td>
                            <td>{{ number_format($row->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="empty-state">No hay datos para el período seleccionado.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header-column">
            <h3>Eventos por Cámara</h3>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cámara</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cameraSummary as $row)
                        <tr>
                            <td>{{ reportCameraLabel($row->camera_id) }}</td>
                            <td>{{ number_format($row->total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                <div class="empty-state">No hay datos para el período seleccionado.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Listado Detallado</h3>

        <div class="toolbar-right">
            <a href="{{ route('reports.export.csv', request()->query()) }}" class="btn btn-secondary">
                Exportar CSV
            </a>

            <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn btn-primary">
                Exportar PDF
            </a>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Evento</th>
                    <th>Fecha</th>
                    <th>Cámara</th>
                    <th>Escenario</th>
                    <th>Tipo</th>
                    <th>Violaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td title="{{ $event->event_id }}">
                            <a href="{{ route('events.show', $event->event_id) }}" class="link-primary">
                                {{ \Illuminate\Support\Str::limit($event->event_id, 30) }}
                            </a>
                        </td>
                        <td>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</td>
                        <td>{{ reportCameraLabel($event->camera_id) }}</td>
                        <td>{{ reportScenarioLabel($event->scenario_id) }}</td>
                        <td>{{ reportEventTypeLabel($event->event_type) }}</td>
                        <td>
                            <div class="badge-group">
                                @foreach(reportViolationLabels($event->violation_codes_json ?? [], $event->event_type) as $violation)
                                    <span class="badge {{ in_array($violation, ['Resuelto', 'Sin violaciones']) ? 'success' : 'danger' }}">
                                        {{ $violation }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">No hay eventos para el período seleccionado.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection