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
@endphp

@section('content')
<style>
    .review-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    .review-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .review-card-media {
        aspect-ratio: 16 / 9;
        background: #f8fbff;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .review-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .review-card-body {
        padding: 14px;
        display: grid;
        gap: 12px;
    }

    .review-card-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .review-card-title h2 {
        margin: 0;
        font-size: 16px;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .review-meta {
        display: grid;
        gap: 7px;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
    }

    .review-status-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .review-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }

    .review-actions .btn {
        width: 100%;
        min-width: 0;
        max-width: none;
        height: 42px;
        padding: 8px 10px;
        font-size: 13px;
        border-radius: 10px;
    }

    .review-actions .btn-danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .review-actions .btn-warning {
        background: #fff7ed;
        color: #b45309;
        border: 1px solid #fed7aa;
    }

    .review-placeholder {
        color: var(--muted);
        font-weight: 700;
        font-size: 13px;
    }

    .review-filter-actions {
        display: flex;
        align-items: end;
        gap: 10px;
        margin-top: 18px;
    }

    .review-filter-actions .btn {
        width: auto;
    }

    .review-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin: 16px 0;
    }

    .review-metric-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: var(--shadow);
        padding: 16px;
    }

    .review-metric-value {
        color: var(--text);
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
    }

    .review-metric-label {
        color: var(--muted);
        font-size: 13px;
        font-weight: 700;
        margin-top: 8px;
    }

    @media (max-width: 980px) {
        .review-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .review-actions {
            grid-template-columns: 1fr;
        }

        .review-metrics-grid {
            grid-template-columns: 1fr;
        }

        .review-filter-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .review-filter-actions .btn {
            width: 100%;
            max-width: none;
        }
    }
</style>

<div class="page-header">
    <div>
        <h1>Validación manual</h1>
        <p class="topbar-subtitle">Revisión compacta de eventos detectados por el sistema.</p>
    </div>
</div>

@if(session('status'))
    <div class="alert-box">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert-box">
        {{ $errors->first() }}
    </div>
@endif

<div class="card">
    <form method="GET" action="{{ route('events.review') }}">
        <div class="filters-grid">
            <div>
                <label for="manual_status" class="field-label">Validación manual</label>
                <select id="manual_status" name="manual_status" class="form-control">
                    <option value="all" @selected($filters['manual_status'] === 'all')>Todos</option>
                    <option value="pending" @selected($filters['manual_status'] === 'pending')>Pendientes</option>
                    <option value="correct" @selected($filters['manual_status'] === 'correct')>Correctos</option>
                    <option value="incorrect" @selected($filters['manual_status'] === 'incorrect')>Incorrectos</option>
                    <option value="false_positive" @selected($filters['manual_status'] === 'false_positive')>Falsos positivos</option>
                    <option value="not_evaluable" @selected($filters['manual_status'] === 'not_evaluable')>No evaluable</option>
                </select>
            </div>

            <div>
                <label for="detected_status" class="field-label">Estado detectado</label>
                <select id="detected_status" name="detected_status" class="form-control">
                    <option value="all" @selected($filters['detected_status'] === 'all')>Todos</option>
                    <option value="compliant" @selected($filters['detected_status'] === 'compliant')>Cumple EPP</option>
                    <option value="non_compliant" @selected($filters['detected_status'] === 'non_compliant')>Incumple EPP</option>
                </select>
            </div>

            <div>
                <label for="camera" class="field-label">Cámara</label>
                <select id="camera" name="camera" class="form-control">
                    <option value="all" @selected($filters['camera'] === 'all')>Todas</option>
                    @foreach($cameras as $camera)
                        <option value="{{ $camera }}" @selected($filters['camera'] === $camera)>
                            {{ $cameraLabel($camera) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="zone" class="field-label">Lugar/zona</label>
                <select id="zone" name="zone" class="form-control">
                    <option value="all" @selected($filters['zone'] === 'all')>Todas</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone }}" @selected($filters['zone'] === $zone)>
                            {{ $zone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="date_from" class="field-label">Fecha desde</label>
                <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" class="form-control">
            </div>

            <div>
                <label for="date_to" class="field-label">Fecha hasta</label>
                <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" class="form-control">
            </div>
        </div>

        <div class="review-filter-actions">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('events.review') }}" class="btn btn-secondary fix-width-button">Limpiar filtros</a>
        </div>
    </form>
</div>

<div class="review-metrics-grid">
    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($metrics['accuracy'], 1) }}%</div>
        <div class="review-metric-label">Precisión general</div>
    </div>

    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($metrics['false_positive_rate'], 1) }}%</div>
        <div class="review-metric-label">Falsos positivos</div>
    </div>

    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($metrics['pending'], 0, ',', '.') }}</div>
        <div class="review-metric-label">Pendientes de revisión</div>
    </div>

    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($metrics['total_validated'], 0, ',', '.') }}</div>
        <div class="review-metric-label">Eventos validados</div>
    </div>
</div>

<div class="review-grid">
    @forelse($events as $event)
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
                    <h2 title="{{ $event->display_id }}">{{ Str::limit($event->display_id, 42) }}</h2>
                    <span class="badge {{ $manualStatusClass($event->manual_status) }}">
                        {{ $manualStatusLabel($event->manual_status) }}
                    </span>
                </div>

                <div class="review-meta">
                    <span>{{ $event->zone_name ?: $scenarioLabel($event->scenario_id) }} | {{ $cameraLabel($event->camera_id) }}</span>
                    <span>{{ optional($event->event_confirmed_at)->format('d-m-Y H:i:s') ?? 'Sin fecha' }}</span>
                </div>

                <div class="review-status-row">
                    <span class="summary-label">Estado detectado</span>
                    <span class="badge {{ $detectedStatusClass($event->status) }}">
                        {{ $detectedStatusLabel($event->status) }}
                    </span>
                </div>

                <div>
                    <span class="summary-label">Validación manual</span>
                    <div class="review-actions">
                        <form method="POST" action="{{ route('events.review.store', array_merge(['eventId' => $event->event_id], request()->query())) }}">
                            @csrf
                            <input type="hidden" name="manual_status" value="correct">
                            <button type="submit" class="btn btn-primary">✓ Correcto</button>
                        </form>

                        <form method="POST" action="{{ route('events.review.store', array_merge(['eventId' => $event->event_id], request()->query())) }}">
                            @csrf
                            <input type="hidden" name="manual_status" value="incorrect">
                            <button type="submit" class="btn btn-danger">✕ Incorrecto</button>
                        </form>

                        <form method="POST" action="{{ route('events.review.store', array_merge(['eventId' => $event->event_id], request()->query())) }}">
                            @csrf
                            <input type="hidden" name="manual_status" value="false_positive">
                            <button type="submit" class="btn btn-warning">⚠ Falso positivo</button>
                        </form>
                    </div>
                </div>

                @if($event->manual_validated_at)
                    <div class="helper-text">
                        Validado por {{ optional($event->manualValidatedBy)->name ?? 'usuario no disponible' }}
                        el {{ $event->manual_validated_at->format('d-m-Y H:i:s') }}
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="empty-state-card">
            <h3 class="empty-state-title">Sin eventos disponibles</h3>
            <p class="empty-state-description">No hay eventos para validar manualmente.</p>
        </div>
    @endforelse
</div>

<div class="mt-16">
    @if($events->hasPages())
        @php
            $currentPage = $events->currentPage();
            $lastPage = $events->lastPage();
            $candidatePages = [1, $lastPage];

            for ($page = $currentPage - 1; $page <= $currentPage + 1; $page++) {
                if ($page >= 1 && $page <= $lastPage) {
                    $candidatePages[] = $page;
                }
            }

            if ($currentPage <= 3) {
                $candidatePages = array_merge($candidatePages, range(1, min(4, $lastPage)));
            }

            if ($currentPage >= $lastPage - 2) {
                $candidatePages = array_merge($candidatePages, range(max(1, $lastPage - 3), $lastPage));
            }

            $pages = array_values(array_unique($candidatePages));
            sort($pages);
            $previousPrintedPage = null;
        @endphp

        <nav class="custom-pagination-wrapper" role="navigation" aria-label="Paginación">
            <div class="custom-pagination-summary">
                Mostrando {{ $events->firstItem() }} a {{ $events->lastItem() }} de {{ $events->total() }} eventos
            </div>

            <div class="custom-pagination">
                @if($events->onFirstPage())
                    <span class="custom-page-btn disabled">Anterior</span>
                @else
                    <a href="{{ $events->previousPageUrl() }}" class="custom-page-btn">Anterior</a>
                @endif

                @foreach($pages as $page)
                    @if($previousPrintedPage !== null && $page > $previousPrintedPage + 1)
                        <span class="custom-page-btn dots">...</span>
                    @endif

                    @if($page === $currentPage)
                        <span class="custom-page-btn active">{{ $page }}</span>
                    @else
                        <a href="{{ $events->url($page) }}" class="custom-page-btn">{{ $page }}</a>
                    @endif

                    @php($previousPrintedPage = $page)
                @endforeach

                @if($events->hasMorePages())
                    <a href="{{ $events->nextPageUrl() }}" class="custom-page-btn">Siguiente</a>
                @else
                    <span class="custom-page-btn disabled">Siguiente</span>
                @endif
            </div>
        </nav>
    @endif
</div>
@endsection
