@extends('layouts.app')

@php
    use Illuminate\Support\Str;

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
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Eventos pendientes de gestión</h1>
            <p class="topbar-subtitle">Eventos pendientes de notificación y cierre humano.</p>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('events.open') }}" class="filters-grid" onsubmit="this.querySelector('button[type=submit]')?.classList.add('is-loading');">
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
                <label for="scenario" class="field-label">Zona</label>
                <select id="scenario" name="scenario" class="form-control">
                    <option value="all">Todas</option>
                    @foreach($scenarios as $scenario)
                        <option value="{{ $scenario }}" @selected($filters['scenario'] === $scenario)>
                            {{ $scenarioLabel($scenario) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search" class="field-label">Buscar</label>
                <input id="search" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Código evento">
            </div>

            <div class="report-actions-cell">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('events.open') }}" class="btn btn-secondary">Limpiar</a>
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
                        <th>Zona</th>
                        <th>Incumplimiento</th>
                        <th>Gestión</th>
                        <th>Acción</th>
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
                            <td><span class="badge warning">Pendiente</span></td>
                            <td>
                                <a href="{{ route('events.open.show', $event->event_id) }}" class="link-primary">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state-card">
                                    <h3 class="empty-state-title">Sin eventos pendientes</h3>
                                    <p class="empty-state-description">No hay incumplimientos pendientes de gestión humana.</p>
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
