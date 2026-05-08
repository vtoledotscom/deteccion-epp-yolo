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
    $statusMessage = session('status') === 'Notificación enviada y evento gestionado correctamente.'
        ? 'Notificación enviada y evento gestionado correctamente.'
        : session('status');
@endphp

@section('content')
    <div class="page-back">
        <a href="{{ route('events.open') }}" class="link-primary">← Volver a Eventos</a>
    </div>

    @if(session('status'))
        <x-alert type="status">{{ $statusMessage }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="validation" :messages="$errors->all()" />
    @endif

    <div class="event-header">
        <div>
            <h2>{{ $event->display_id }}</h2>
            <p>Detectado el {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</p>
        </div>
    </div>

    <div class="page-header">
        <h1>Resumen del evento</h1>
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

        {{-- <div class="summary-item">
            <span class="summary-label">Estado detector</span>
            <strong>{{ $event->status }}</strong>
        </div> --}}

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
            <div>
                <h2>Evidencia destacada</h2>
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
            <div>
                <h2>Gestión de notificación de evento</h2>
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
                <form method="POST" action="{{ route('events.open.resolve', $event->event_id) }}" class="info-section" onsubmit="if (!confirm('¿Confirmas que la persona fue notificada y deseas cerrar este evento?')) { return false; } this.querySelector('button[type=submit]')?.classList.add('is-loading');">
                    @csrf

                    <div class="info-block">
                        <label for="notified_person" class="field-label">Persona notificada</label>
                        <input id="notified_person" name="notified_person" value="{{ old('notified_person') }}" class="form-control input-gradient-focus" maxlength="255" required>
                        <p class="helper-text">Indica a quién se informó el incumplimiento.</p>
                    </div>

                    <div class="info-block">
                        <label for="notification_method" class="field-label">Método de notificación</label>
                        <select id="notification_method" name="notification_method" class="form-control input-gradient-focus" required>
                            <option value="">Seleccionar</option>
                            @foreach($notificationMethods as $method)
                                <option value="{{ $method }}" @selected(old('notification_method') === $method)>
                                    {{ $methodLabel($method) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="helper-text">Selecciona el canal usado para la notificación.</p>
                    </div>

                    <div class="info-block">
                        <label for="resolution_note" class="field-label">Observación / motivo de cierre</label>
                        <textarea id="resolution_note" name="resolution_note" class="form-control input-gradient-focus" rows="5" minlength="5" maxlength="2000" required>{{ old('resolution_note') }}</textarea>
                        <p class="helper-text danger-helper">Este cierre dejará el evento fuera de la lista de pendientes.</p>
                    </div>

                    <button type="submit" class="btn btn-gradient-primary">Notificar y cerrar evento</button>
                </form>
            @else
                <div class="empty-state">No tienes permisos para cerrar este evento.</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div>
            <h2>Historial de acciones</h2>
        </div>

        <div class="info-section">
            @forelse($event->actions->sortByDesc('created_at') as $action)
                <div class="info-block">
                    <strong>{{ optional($action->user)->name ?? 'N/D' }}</strong>
                    <span> realizó acción <span class="badge-gradient-primary badge">{{ $actionLabel($action->action) }}</span> el {{ optional($action->created_at)->format('d-m-Y H:i:s') }}.</span>
                    @if($action->notified_person || $action->notification_method)
                        <div class="topbar-subtitle">Notificado: {{ $action->notified_person ?? 'N/D' }} · Método: {{ $methodLabel($action->notification_method) }}</div>
                    @endif
                    @if($action->note)
                        <div class="topbar-subtitle">{{ $action->note }}</div>
                    @endif
                </div>
            @empty
                <div class="empty-state-card">
                    <h3 class="empty-state-title">Sin acciones registradas</h3>
                    <p class="empty-state-description">Cuando se comente o cierre el evento, el historial aparecerá aquí.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
