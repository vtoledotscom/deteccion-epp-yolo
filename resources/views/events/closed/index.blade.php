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
    $methodLabel = fn (?string $method) => match ($method) {
        'verbal' => 'Verbal',
        'email' => 'Email',
        'telefono' => 'Teléfono',
        'supervisor_directo' => 'Supervisor directo',
        'otro' => 'Otro',
        default => $method ?: 'N/D',
    };
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Eventos Cerrados</h1>
            <p class="topbar-subtitle">Eventos no conformes ya notificados y cerrados por un usuario.</p>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('events.closed') }}" class="filters-grid">
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
            </div>
        </form>

        <div class="toolbar-left">
            <a href="{{ route('events.closed') }}" class="btn btn-secondary">Limpiar</a>
        </div>
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
                        <th>Persona notificada</th>
                        <th>Método</th>
                        <th>Cerrado por</th>
                        <th>Fecha de cierre</th>
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
                            <td>{{ $event->human_notified_person ?? 'N/D' }}</td>
                            <td>{{ $methodLabel($event->human_notification_method) }}</td>
                            <td>
                                <strong>{{ optional($event->humanResolvedBy)->name ?? 'N/D' }}</strong><br>
                                <small>{{ optional($event->humanResolvedBy)->email ?? '' }}</small>
                            </td>
                            <td>{{ optional($event->human_resolved_at)->format('d-m-Y H:i:s') }}</td>
                            <td>
                                <a href="{{ route('events.closed.show', $event->event_id) }}" class="link-primary">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty-state">No hay eventos cerrados para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-16">
            {{ $events->links('vendor.livewire.epp-pagination') }}
        </div>
    </div>
@endsection
