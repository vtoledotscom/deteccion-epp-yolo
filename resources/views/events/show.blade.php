@extends('layouts.app')

@php
    function eventStatusLabel(string $eventType): string {
        return match ($eventType) {
            'violation_started' => 'Abierto',
            'violation_resolved' => 'Resuelto',
            default => ucfirst($eventType),
        };
    }

    function eventStatusClass(string $eventType): string {
        return match ($eventType) {
            'violation_started' => 'warning',
            'violation_resolved' => 'success',
            default => 'warning',
        };
    }

    function eventScenarioLabel(string $scenarioId): string {
        return match ($scenarioId) {
            'helmet_required' => 'Casco obligatorio',
            'vest_required' => 'Chaleco obligatorio',
            'helmet_and_vest_required' => 'Casco y chaleco',
            default => $scenarioId,
        };
    }

    function eventCameraLabel(string $cameraId): string {
        return strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId));
    }

    function eventViolationLabels(array $violations, string $eventType): array {
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

    function eventComplianceLabel(string $status): string {
        return match ($status) {
            'non_compliant' => 'Infracción',
            'compliant' => 'Cumple',
            default => $status,
        };
    }

    function eventObservedStatusLabel(string $status): string {
        return match ($status) {
            'non_compliant' => 'Infracción observada',
            'compliant' => 'Cumple observado',
            default => $status,
        };
    }
@endphp

@section('content')
<div class="page-back">
    <a href="{{ route('events.index') }}" class="link-primary">← Volver a Eventos</a>
</div>

<div class="event-header">
    <div>
        <h2 title="{{ $event->display_id }}">{{ \Illuminate\Support\Str::limit($event->display_id, 60) }}</h2>
        <p>Detectado el {{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') }}</p>
    </div>
</div>

<div class="page-header">
    <h2>Resumen del evento</h2>
</div>


<div class="summary-grid">
    <div class="summary-item">
        <span class="summary-label">Cámara</span>
        <strong>{{ eventCameraLabel($event->camera_id) }}</strong>
    </div>

    <div class="summary-item">
        <span class="summary-label">Escenario</span>
        <strong>{{ eventScenarioLabel($event->scenario_id) }}</strong>
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
            <div class="evidence-placeholder">
                Imagen no disponible
            </div>
        @endif

        <div class="stack-actions">
            @if(auth()->user()?->hasPermission('export_pdf'))
                <a href="{{ route('events.export.event-pdf', $event->event_id) }}" class="btn btn-primary">
                    Descargar PDF
                </a>
            @endif

            @if($event->human_review_status === 'pending' && auth()->user()?->hasPermission('resolve_open_events'))
                <a href="{{ route('events.open.show', $event->event_id) }}" class="btn btn-primary">
                    Gestionar notificación
                </a>
            @elseif($event->human_review_status === 'resolved' && auth()->user()?->hasPermission('view_open_events'))
                <a href="{{ route('events.closed.show', $event->event_id) }}" class="btn btn-secondary">
                    Ver cierre
                </a>
            @endif

            @if(optional($event->evidence)->image_annotated_path)
                <a href="{{ route('media.events.annotated', $event->event_id) }}" target="_blank" class="btn btn-secondary">
                    Ver Imagen Anotada
                </a>
            @else
                <button class="btn btn-secondary" disabled>Imagen anotada no disponible</button>
            @endif

            @if(optional($event->evidence)->image_full_path)
                <a href="{{ route('media.events.full', $event->event_id) }}" target="_blank" class="btn btn-secondary">
                    Ver Imagen Full
                </a>
            @else
                <button class="btn btn-secondary" disabled>Imagen full no disponible</button>
            @endif

            @if(optional($event->evidence)->image_crop_path)
                <a href="{{ route('media.events.crop', $event->event_id) }}" target="_blank" class="btn btn-secondary">
                    Ver Crop
                </a>
            @else
                <button class="btn btn-secondary" disabled>Crop no disponible</button>
            @endif

            @if(optional($event->evidence)->video_path)
                <a href="{{ route('media.events.video', $event->event_id) }}" target="_blank" class="btn btn-secondary">
                    Ver Video
                </a>
            @else
                <button class="btn btn-secondary" disabled>Video no disponible</button>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="tabs">
            <button class="tab" type="button">Detalle evento</button>
        </div>

        <div class="info-section">
            <div class="info-block">
                <span class="info-label">Estado actual</span>
                <span class="badge {{ eventStatusClass($event->event_type) }}">
                    {{ eventComplianceLabel($event->status) }}
                </span>
            </div>

            <div class="info-block">
                <span class="info-label">Resultado del evento</span>
                <span class="badge {{ eventStatusClass($event->event_type) }}">
                    {{ eventStatusLabel($event->event_type) }}
                </span>
            </div>

            <div class="info-block">
                <span class="info-label">Violaciones detectadas</span>
                <div class="badge-group">
                    @foreach(eventViolationLabels($event->violation_codes_json ?? [], $event->event_type) as $violation)
                        <span class="badge {{ in_array($violation, ['Resuelto', 'Sin violaciones']) ? 'success' : 'danger' }}">
                            {{ $violation }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="info-block">
                <span class="info-label">Cámara</span>
                <span>{{ eventCameraLabel($event->camera_id) }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Escenario</span>
                <span>{{ eventScenarioLabel($event->scenario_id) }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Zona de evaluación</span>
                <span>{{ $event->zone_name }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Estado observado</span>
                <span>{{ eventObservedStatusLabel($event->observed_status) }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Casco detectado correctamente</span>
                <span>{{ $event->helmet_ok ? 'Sí' : 'No' }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Chaleco detectado correctamente</span>
                <span>{{ $event->vest_ok ? 'Sí' : 'No' }}</span>
            </div>

            <div class="info-block">
                <span class="info-label">Fecha de resolución</span>
                <span>{{ optional($event->resolved_at)->format('d-m-Y H:i:s') ?? 'No resuelto' }}</span>
            </div>
        </div>
    </div>
</div>

@endsection
