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
        <a href="{{ route('events.closed') }}" class="link-primary">← Volver a Eventos</a>
    </div>

    <div class="event-header">
        <div>
            <h2>{{ $event->display_id }}</h2>
            <p>Detectado el {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</p>
        </div>

    </div>

    <div class="page-header">
        <h2>Resumen del evento</h2>
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
                <h2>Información de cierre</h2>
            </div>

            <div class="info-section">
                <div class="info-block">
                    <span class="info-label">Cerrado por</span>
                    <span>{{ optional($event->humanResolvedBy)->name ?? 'N/D' }} · {{ optional($event->humanResolvedBy)->email ?? '' }}</span>
                </div>
                <div class="info-block">
                    <span class="info-label">Fecha de cierre</span>
                    <span class="badge success">{{ optional($event->human_resolved_at)->format('d-m-Y H:i:s') }}</span>
                </div>
                <div class="info-block">
                    <span class="info-label">Persona notificada</span>
                    <span>{{ $event->human_notified_person ?? 'N/D' }}</span>
                </div>
                <div class="info-block">
                    <span class="info-label">Método de notificación</span>
                    <span>{{ $methodLabel($event->human_notification_method) }}</span>
                </div>
                <div class="info-block">
                    <span class="info-label">Observación</span>
                    <span>{{ $event->human_resolution_note ?? 'N/D' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="">
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
                    <p class="empty-state-description">No hay historial operativo asociado a este evento.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
