@php
    use Illuminate\Support\Str;

    $cameraLabel = fn (?string $camera) => $camera ? strtoupper(str_replace('cam_rtsp_', 'CAM ', $camera)) : 'N/D';
    $scenarioLabel = fn (?string $scenario) => match ($scenario) {
        'helmet_required' => 'Casco obligatorio',
        'vest_required' => 'Chaleco obligatorio',
        'helmet_and_vest_required' => 'Casco y chaleco',
        default => $scenario ?: 'N/D',
    };
    $detectedStatusLabel = fn (?string $status) => match ($status) {
        'compliant' => 'Cumple EPP',
        'non_compliant' => 'Incumple EPP',
        default => $status ?: 'N/D',
    };
    $detectedStatusClass = fn (?string $status) => match ($status) {
        'compliant' => 'success',
        'non_compliant' => 'danger',
        default => 'warning',
    };
    $manualStatusLabel = fn (?string $status) => match ($status) {
        'correct' => 'Correcto',
        'incorrect' => 'Incorrecto',
        'false_positive' => 'Falso positivo',
        'not_evaluable' => 'No evaluable',
        default => 'Pendiente',
    };
    $manualStatusClass = fn (?string $status) => match ($status) {
        'correct' => 'success',
        'incorrect', 'false_positive' => 'danger',
        'not_evaluable' => 'warning',
        default => 'warning',
    };
    $validationRouteName ??= 'events.review.store';
    $validationStatuses ??= ['correct', 'false_positive'];
    $canValidate ??= auth()->user()?->hasPermission('review_detection_events');
@endphp

<article class="review-card">
    <div class="review-card-media">
        @if(optional($event->evidence)->image_annotated_path)
            <img src="{{ route('media.events.annotated', $event->event_id) }}" alt="Imagen anotada del evento {{ $event->display_id }}">
        @else
            <div class="review-placeholder">Imagen no disponible</div>
        @endif
    </div>

    <div class="review-card-body">
        <div class="review-card-title">
            <h2>
                <a href="{{ route('events.show', $event->event_id) }}" class="link-primary" title="{{ $event->display_id }}">
                    {{ Str::limit($event->display_id, 42) }}
                </a>
            </h2>
            <span class="badge {{ $detectedStatusClass($event->status) }}">
                {{ $detectedStatusLabel($event->status) }}
            </span>
        </div>

        <div class="review-meta">
            <span>{{ $event->zone_name ?: $scenarioLabel($event->scenario_id) }} | {{ $cameraLabel($event->camera_id) }}</span>
            <span>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') ?? 'Sin fecha' }}</span>
        </div>

        <div class="review-status-row">
            <span class="summary-label">Validación manual</span>
            <span class="badge {{ $manualStatusClass($event->manual_status) }}">
                {{ $manualStatusLabel($event->manual_status) }}
            </span>
        </div>

        <div>
            @if($event->manual_status === null && $canValidate)
                <div class="review-actions">
                    @if(in_array('correct', $validationStatuses, true))
                        <form method="POST" action="{{ route($validationRouteName, array_merge(['eventId' => $event->event_id], request()->query())) }}">
                            @csrf
                            <input type="hidden" name="manual_status" value="correct">
                            <button type="submit" class="btn btn-primary">Correcto</button>
                        </form>
                    @endif

                    @if(in_array('false_positive', $validationStatuses, true))
                        <form method="POST" action="{{ route($validationRouteName, array_merge(['eventId' => $event->event_id], request()->query())) }}">
                            @csrf
                            <input type="hidden" name="manual_status" value="false_positive">
                            <button type="submit" class="btn btn-warning">Falso positivo</button>
                        </form>
                    @endif
                </div>
            @elseif($event->manual_status === null)
                <div class="helper-text">No tienes permiso para validar eventos.</div>
            @else
                <div class="helper-text">Evento validado. La card queda en modo solo lectura.</div>
            @endif
        </div>

        @if($event->manual_validated_at)
            <div class="helper-text">
                Validado por {{ optional($event->manualValidatedBy)->name ?? 'usuario no disponible' }}
                el {{ $event->manual_validated_at->format('d-m-Y H:i:s') }}
            </div>
        @endif
    </div>
</article>
