@extends('layouts.app')

@php
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
    $actionLabel = fn (?string $action) => match ($action) {
        'resolved' => 'Notificado y cerrado',
        'commented' => 'Comentario',
        default => $action ?: 'N/D',
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
    <div class="page-back">
        <a href="{{ route('events.open') }}" class="link-primary">← Volver a Eventos abiertos</a>
    </div>

    @if(session('status'))
        <div class="card">
            <span class="badge success">{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="card">
            @foreach($errors->all() as $error)
                <div class="badge danger">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="event-header">
        <div>
            <h2>{{ $event->display_id }}</h2>
            <p>Detectado el {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</p>
        </div>

        <span class="badge {{ $event->human_review_status === 'resolved' ? 'success' : 'warning' }} large">
            {{ $event->human_review_status === 'resolved' ? 'Resuelto' : 'Pendiente' }}
        </span>
    </div>

    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label">Cámara</span>
            <strong>{{ $cameraLabel($event->camera_id) }}</strong>
        </div>

        <div class="summary-item">
            <span class="summary-label">Zona</span>
            <strong>{{ $event->zone_name ?: $scenarioLabel($event->scenario_id) }}</strong>
        </div>

        <div class="summary-item">
            <span class="summary-label">Estado detector</span>
            <strong>{{ $event->status }}</strong>
        </div>

        <div class="summary-item">
            <span class="summary-label">Incumplimiento</span>
            <div class="badge-group">
                @foreach($violationLabels($event->violation_codes_json ?? []) as $violation)
                    <span class="badge danger">{{ $violation }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="detail-grid">
        <div class="card">
            <div class="card-header-column">
                <h3>Evidencia</h3>
            </div>

            @if(optional($event->evidence)->image_annotated_path)
                <div class="evidence-media">
                    <img
                        src="{{ route('media.events.annotated', $event->event_id) }}"
                        alt="Imagen anotada del evento"
                        class="evidence-image"
                        onerror="this.outerHTML='<div class=\'evidence-placeholder\'>Imagen no disponible</div>'"
                    >
                </div>
            @else
                <div class="evidence-placeholder">Imagen no disponible</div>
            @endif

            <div class="stack-actions">
                @if(optional($event->evidence)->image_annotated_path)
                    <a href="{{ route('media.events.annotated', $event->event_id) }}" target="_blank" class="btn btn-secondary">Ver Imagen Anotada</a>
                @endif

                @if(optional($event->evidence)->image_full_path)
                    <a href="{{ route('media.events.full', $event->event_id) }}" target="_blank" class="btn btn-secondary">Ver Imagen Full</a>
                @endif

                @if(optional($event->evidence)->image_crop_path)
                    <a href="{{ route('media.events.crop', $event->event_id) }}" target="_blank" class="btn btn-secondary">Ver Crop</a>
                @endif

                @if(optional($event->evidence)->video_path)
                    <a href="{{ route('media.events.video', $event->event_id) }}" target="_blank" class="btn btn-secondary">Ver Video</a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header-column">
                <h3>Gestión de notificación de evento</h3>
            </div>

            @if($event->human_review_status === 'resolved')
                <div class="info-section">
                    <div class="info-block">
                        <span class="info-label">Cerrado por</span>
                        <span>{{ optional($event->humanResolvedBy)->name ?? 'N/D' }} · {{ optional($event->humanResolvedBy)->email ?? '' }}</span>
                    </div>
                    <div class="info-block">
                        <span class="info-label">Fecha de cierre</span>
                        <span>{{ optional($event->human_resolved_at)->format('d-m-Y H:i:s') }}</span>
                    </div>
                    <div class="info-block">
                        <span class="info-label">Persona notificada</span>
                        <span>{{ $event->human_notified_person }}</span>
                    </div>
                    <div class="info-block">
                        <span class="info-label">Método de notificación</span>
                        <span>{{ $methodLabel($event->human_notification_method) }}</span>
                    </div>
                    <div class="info-block">
                        <span class="info-label">Observación</span>
                        <span>{{ $event->human_resolution_note }}</span>
                    </div>
                </div>
            @elseif(auth()->user()?->hasPermission('resolve_open_events'))
                <form method="POST" action="{{ route('events.open.resolve', $event->event_id) }}" class="info-section">
                    @csrf

                    <div class="info-block">
                        <label for="notified_person" class="field-label">Persona notificada</label>
                        <input id="notified_person" name="notified_person" value="{{ old('notified_person') }}" class="form-control" maxlength="255" required>
                    </div>

                    <div class="info-block">
                        <label for="notification_method" class="field-label">Método de notificación</label>
                        <select id="notification_method" name="notification_method" class="form-control" required>
                            <option value="">Seleccionar</option>
                            @foreach($notificationMethods as $method)
                                <option value="{{ $method }}" @selected(old('notification_method') === $method)>
                                    {{ $methodLabel($method) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="info-block">
                        <label for="resolution_note" class="field-label">Observación / motivo de cierre</label>
                        <textarea id="resolution_note" name="resolution_note" class="form-control" rows="5" minlength="5" maxlength="2000" required>{{ old('resolution_note') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Notificar y cerrar evento</button>
                </form>
            @else
                <div class="empty-state">No tienes permisos para cerrar este evento.</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header-column">
            <h3>Historial de acciones</h3>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Notificado</th>
                        <th>Método</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($event->actions->sortByDesc('created_at') as $action)
                        <tr>
                            <td>{{ optional($action->created_at)->format('d-m-Y H:i:s') }}</td>
                            <td>{{ $actionLabel($action->action) }}</td>
                            <td>
                                <strong>{{ optional($action->user)->name ?? 'N/D' }}</strong><br>
                                <small>{{ optional($action->user)->email ?? '' }}</small>
                            </td>
                            <td>{{ $action->notified_person ?? 'N/D' }}</td>
                            <td>{{ $methodLabel($action->notification_method) }}</td>
                            <td>{{ $action->note }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">No hay acciones registradas para este evento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
