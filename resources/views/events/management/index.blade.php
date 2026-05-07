@extends('layouts.app')

@php
    use Illuminate\Support\Str;

    $tabs = [
        'pending' => 'Pendientes',
        'closed' => 'Cerrados',
        'all' => 'Todos',
    ];
    $cameraLabel = fn (?string $camera) => $camera ? strtoupper(str_replace('cam_rtsp_', 'CAM ', $camera)) : 'N/D';
    $scenarioLabel = fn (?string $scenario) => match ($scenario) {
        'helmet_required' => 'Casco obligatorio',
        'vest_required' => 'Chaleco obligatorio',
        'helmet_and_vest_required' => 'Casco y chaleco',
        default => $scenario ?: 'N/D',
    };
    $violationLabels = function (?array $violations): array {
        if (empty($violations)) {
            return ['Sin detalle'];
        }

        return array_map(fn ($violation) => match ($violation) {
            'missing_helmet' => 'Sin casco',
            'missing_vest' => 'Sin chaleco',
            default => $violation,
        }, $violations);
    };
    $managementStatusLabel = fn (?string $status) => match ($status) {
        'resolved' => 'Cerrado',
        'pending' => 'Pendiente',
        default => $status ?: 'N/D',
    };
    $managementStatusClass = fn (?string $status) => match ($status) {
        'resolved' => 'success',
        'pending' => 'warning',
        default => 'warning',
    };
@endphp

@section('content')
    <style>
        .management-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .management-tab {
            min-height: 40px;
            padding: 9px 14px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: #fff;
            color: var(--text);
            font-size: 13px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
        }

        .management-tab:hover {
            border-color: var(--primary-dark);
            color: var(--primary-dark);
        }

        .management-tab.active {
            background: var(--gradient-primary);
            border-color: var(--primary-blue);
            color: #fff;
            box-shadow: 0 10px 20px rgba(56, 136, 235, 0.18);
        }

        .management-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .management-actions .btn,
        .management-actions .link-primary {
            width: auto;
            min-width: 0;
            max-width: none;
            height: 38px;
            padding: 8px 12px;
            font-size: 13px;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .management-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .management-actions .btn,
            .management-actions .link-primary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Gestión operacional de eventos</h1>
            <p class="topbar-subtitle">Bandeja unificada para revisar eventos pendientes, cerrados y el histórico operativo.</p>
        </div>
    </div>

    <div class="management-tabs" role="navigation" aria-label="Filtros rápidos de gestión">
        @foreach($tabs as $tabKey => $tabLabel)
            @php
                $tabUrl = route('events.management', array_merge(
                    request()->except(['page', 'tab']),
                    ['tab' => $tabKey]
                ));
            @endphp

            <a
                href="{{ $tabUrl }}"
                class="management-tab {{ $filters['tab'] === $tabKey ? 'active' : '' }}"
                aria-current="{{ $filters['tab'] === $tabKey ? 'page' : 'false' }}"
            >
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    <div class="card">
        <form method="GET" action="{{ route('events.management') }}" class="filters-grid" onsubmit="this.querySelector('button[type=submit]')?.classList.add('is-loading');">
            <input type="hidden" name="tab" value="{{ $filters['tab'] }}">

            <div>
                <label for="camera" class="field-label">Cámara</label>
                <select id="camera" name="camera" class="form-control">
                    <option value="all">Todas</option>
                    @foreach($cameras as $camera)
                        <option value="{{ $camera }}" @selected($filters['camera'] === $camera)>
                            {{ $cameraLabel($camera) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="scenario" class="field-label">Escenario</label>
                <select id="scenario" name="scenario" class="form-control">
                    <option value="all">Todos</option>
                    @foreach($scenarios as $scenario)
                        <option value="{{ $scenario }}" @selected($filters['scenario'] === $scenario)>
                            {{ $scenarioLabel($scenario) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="zone" class="field-label">Zona</label>
                <select id="zone" name="zone" class="form-control">
                    <option value="all">Todas</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone }}" @selected($filters['zone'] === $zone)>
                            {{ $zone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search" class="field-label">Buscar</label>
                <input id="search" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Buscar evento, estado o incumplimiento">
                <small class="topbar-subtitle">Ejemplos: pendiente, incumplimiento, falso positivo, EVT-000001</small>
            </div>

            <div>
                <label for="date_from" class="field-label">Fecha desde</label>
                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" class="form-control">
            </div>

            <div>
                <label for="date_to" class="field-label">Fecha hasta</label>
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" class="form-control">
            </div>

            <div class="report-actions-cell">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('events.management', ['tab' => $filters['tab']]) }}" class="btn btn-secondary fix-width-button">Limpiar filtros</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código evento</th>
                        <th>Fecha detección</th>
                        <th>Cámara</th>
                        <th>Escenario/Zona</th>
                        <th>Incumplimiento</th>
                        <th>Gestión</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($events as $event)
                        <tr>
                            <td title="{{ $event->display_id }}">{{ Str::limit($event->display_id, 30) }}</td>
                            <td>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $cameraLabel($event->camera_id) }}</td>
                            <td>{{ $event->zone_name ?: $scenarioLabel($event->scenario_id) }}</td>
                            <td>
                                <div class="badge-group">
                                    @foreach($violationLabels($event->violation_codes_json ?? []) as $violation)
                                        <span class="badge danger">{{ $violation }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $managementStatusClass($event->human_review_status) }}">
                                    {{ $managementStatusLabel($event->human_review_status) }}
                                </span>
                            </td>
                            <td>
                                <div class="management-actions">
                                    @if($event->human_review_status === 'pending' && auth()->user()?->hasPermission('resolve_open_events'))
                                        <a href="{{ route('events.open.show', $event->event_id) }}" class="btn btn-primary">
                                            Gestionar notificación
                                        </a>
                                    @endif

                                    @if(auth()->user()?->hasPermission('export_pdf'))
                                        <a href="{{ route('events.export.event-pdf', $event->event_id) }}" class="btn btn-primary">
                                            Descargar PDF
                                        </a>
                                    @endif

                                    <a href="{{ route('events.show', $event->event_id) }}" class="link-primary">
                                        Ver detalle
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state-card">
                                    <h3 class="empty-state-title">Sin eventos para mostrar</h3>
                                    <p class="empty-state-description">No hay eventos operativos para los filtros seleccionados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-16">
            {{ $events->links('vendor.pagination.epp') }}
        </div>
    </div>
@endsection
