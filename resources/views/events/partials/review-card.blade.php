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

@once
    <style>
        .review-card.evidence-card {
            position: relative;
            overflow: visible;
            background: transparent;
            border: 0;
            border-radius: 12px;
            box-shadow: none;
        }

        .evidence-card-inner {
            overflow: visible;
            border-radius: 12px;
            background: var(--panel);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .evidence-card:hover .evidence-card-inner {
            transform: translateY(-2px);
            border-color: rgba(56, 136, 235, 0.32);
            box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
        }

        .evidence-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 0px 10px;
        }

        .evidence-card-header h2 {
            margin: 0;
            font-size: 15px;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .evidence-card-header .badge {
            flex: 0 0 auto;
            padding: 4px 8px;
            font-size: 11px;
        }

        .evidence-media {
            position: relative;
            aspect-ratio: 16 / 10;
            background: #f8fbff;
            border-top: 1px solid #edf2f7;
            border-bottom: 1px solid #edf2f7;
        }

        .evidence-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .evidence-placeholder {
            width: 100%;
            height: 100%;
            display: grid;
            place-items: center;
            color: var(--muted);
            font-size: 13px;
            font-weight: 800;
            background: linear-gradient(135deg, #f8fbff, #eef6ff);
        }

        .evidence-preview {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 10px);
            z-index: 80;
            width: min(430px, 82vw);
            max-height: 330px;
            aspect-ratio: 16 / 10;
            border: 1px solid rgba(15, 23, 42, 0.16);
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 24px 54px rgba(15, 23, 42, 0.26);
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, 8px) scale(0.98);
            transition: opacity 0.16s ease, transform 0.16s ease;
            overflow: hidden;
        }

        .evidence-media:hover .evidence-preview {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
        }

        .evidence-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: #fff;
        }

        .evidence-card-body {
            display: grid;
            gap: 11px;
            padding: 12px;
        }

        .evidence-meta {
            display: grid;
            gap: 7px;
        }

        .evidence-meta-item {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            color: var(--text);
            font-size: 13px;
            font-weight: 700;
        }

        .evidence-meta-item span:first-child {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .evidence-meta-item span:last-child {
            min-width: 0;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .evidence-validation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 10px;
            border-top: 1px solid #edf2f7;
        }

        .evidence-validation-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
        }

        .evidence-validation .badge {
            padding: 4px 8px;
            font-size: 11px;
        }

        .evidence-card .review-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .evidence-card .review-actions .btn {
            width: 100%;
            min-width: 0;
            max-width: none;
            height: 38px;
            padding: 7px 9px;
            border-radius: 9px;
            font-size: 13px;
        }

        .evidence-card .review-actions .btn-warning {
            background: #fff7ed;
            color: #b45309;
            border: 1px solid #fed7aa;
        }

        .evidence-card .helper-text {
            margin-top: 0;
            font-size: 12px;
            line-height: 1.35;
        }

        @media (max-width: 700px) {
            .evidence-preview {
                display: none;
            }

            .evidence-card-header {
                align-items: flex-start;
            }

            .evidence-meta-item {
                display: grid;
                gap: 2px;
            }

            .evidence-meta-item span:last-child {
                text-align: left;
            }
        }
    </style>
@endonce

<article class="review-card evidence-card">
    <div class="evidence-card-inner">
        <div class="review-card-media evidence-media">
            @if(optional($event->evidence)->image_annotated_path)
                <img src="{{ route('media.events.annotated', $event->event_id) }}" alt="Imagen anotada del evento {{ $event->display_id }}">
                <div class="evidence-preview" aria-hidden="true">
                    <img src="{{ route('media.events.annotated', $event->event_id) }}" alt="">
                </div>
            @else
                <div class="review-placeholder evidence-placeholder">Imagen no disponible</div>
            @endif
        </div>

        <div class="review-card-body evidence-card-body">
            <div class="review-meta evidence-meta">
                <header class="evidence-card-header">
                    <h2>
                        <a href="{{ route('events.show', $event->event_id) }}" class="link-primary" title="{{ $event->display_id }}">
                            {{ Str::limit($event->display_id, 42) }}
                        </a>
                    </h2>
                    <span class="badge {{ $detectedStatusClass($event->status) }}">
                        {{ $detectedStatusLabel($event->status) }}
                    </span>
                </header>
                <div class="evidence-meta-item">
                    <span>Cámara</span>
                    <span>{{ $cameraLabel($event->camera_id) }}</span>
                </div>
                <div class="evidence-meta-item">
                    <span>Zona</span>
                    <span>{{ $event->zone_name ?: $scenarioLabel($event->scenario_id) }}</span>
                </div>
                <div class="evidence-meta-item">
                    <span>Fecha</span>
                    <span>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i') ?? 'Sin fecha' }}</span>
                </div>
            </div>

            <div class="review-status-row evidence-validation">
                <span class="summary-label evidence-validation-label">Validación manual</span>
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
                    <div class="helper-text">Sin permiso para validar.</div>
                @else
                    <div class="helper-text">Solo lectura.</div>
                @endif
            </div>

            @if($event->manual_validated_at)
                <div class="helper-text">
                    Validado por {{ optional($event->manualValidatedBy)->name ?? 'usuario no disponible' }}
                    el {{ $event->manual_validated_at->format('d-m-Y H:i') }}
                </div>
            @endif
        </div>
    </div>
</article>
