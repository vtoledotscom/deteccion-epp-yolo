@extends('layouts.app')

@php
    function dashboardCameraLabel(?string $cameraId): string {
        return $cameraId ? strtoupper(str_replace('cam_rtsp_', 'CAM ', $cameraId)) : 'N/D';
    }

    function dashboardDetectedStatusLabel(?string $status): string {
        return match ($status) {
            'compliant' => 'Cumple EPP',
            'non_compliant' => 'Incumple EPP',
            default => $status ?: 'N/D',
        };
    }

    $quickFilters = [
        'all' => 'Todos',
        'pending' => 'Pendientes',
        'validated' => 'Validados',
        'false_positive' => 'Falsos positivos',
        'violations' => 'Incumplimientos',
        'compliance' => 'Cumplimientos',
    ];
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
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .kpi-card {
        --kpi-accent: rgba(56, 136, 235, 0.28);
        --kpi-shadow: rgba(15, 23, 42, 0.1);
        border-color: var(--kpi-border, var(--border));
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        border-color: var(--kpi-accent);
        box-shadow: 0 16px 30px var(--kpi-shadow);
    }

    .kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .kpi-icon svg {
        width: 22px;
        height: 22px;
        stroke: currentColor;
        stroke-width: 2;
    }

    .kpi-icon.blue {
        color: #2563eb;
    }

    .kpi-icon.red {
        color: #dc2626;
    }

    .kpi-icon.orange {
        color: #ea580c;
    }

    .kpi-icon.green {
        color: #16a34a;
    }

    .kpi-card.blue {
        --kpi-border: #bfdbfe;
        --kpi-accent: #93c5fd;
        --kpi-shadow: rgba(37, 99, 235, 0.12);
    }

    .kpi-card.red {
        --kpi-border: #fecaca;
        --kpi-accent: #fca5a5;
        --kpi-shadow: rgba(220, 38, 38, 0.12);
    }

    .kpi-card.orange {
        --kpi-border: #fed7aa;
        --kpi-accent: #fdba74;
        --kpi-shadow: rgba(234, 88, 12, 0.12);
    }

    .kpi-card.green {
        --kpi-border: #bbf7d0;
        --kpi-accent: #86efac;
        --kpi-shadow: rgba(22, 163, 74, 0.12);
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

    .quick-filter-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 18px;
    }

    .quick-filter-tab {
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

    .quick-filter-tab:hover {
        border-color: var(--primary-dark);
        color: var(--primary-dark);
    }

    .quick-filter-tab.active {
        background: var(--gradient-primary);
        border-color: var(--primary-blue);
        color: #fff;
        box-shadow: 0 10px 20px rgba(56, 136, 235, 0.18);
    }

    .review-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .review-metric-card {
        --metric-accent: rgba(56, 136, 235, 0.28);
        --metric-shadow: rgba(15, 23, 42, 0.1);
        background: var(--panel);
        border: 1px solid var(--metric-border, var(--border));
        border-radius: 14px;
        box-shadow: var(--shadow);
        padding: 16px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .review-metric-card:hover {
        transform: translateY(-2px);
        border-color: var(--metric-accent);
        box-shadow: 0 16px 30px var(--metric-shadow);
    }

    .review-metric-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        margin-bottom: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--metric-bg);
        color: var(--metric-color);
    }

    .review-metric-icon svg {
        width: 22px;
        height: 22px;
        stroke: currentColor;
        stroke-width: 2;
    }

    .review-metric-card.blue {
        --metric-bg: #dbeafe;
        --metric-color: #2563eb;
        --metric-border: #bfdbfe;
        --metric-accent: #93c5fd;
        --metric-shadow: rgba(37, 99, 235, 0.12);
    }

    .review-metric-card.orange {
        --metric-bg: #ffedd5;
        --metric-color: #ea580c;
        --metric-border: #fed7aa;
        --metric-accent: #fdba74;
        --metric-shadow: rgba(234, 88, 12, 0.12);
    }

    .review-metric-card.yellow {
        --metric-bg: #fef3c7;
        --metric-color: #b45309;
        --metric-border: #fde68a;
        --metric-accent: #fcd34d;
        --metric-shadow: rgba(180, 83, 9, 0.12);
    }

    .review-metric-card.green {
        --metric-bg: #dcfce7;
        --metric-color: #16a34a;
        --metric-border: #bbf7d0;
        --metric-accent: #86efac;
        --metric-shadow: rgba(22, 163, 74, 0.12);
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
        .review-actions,
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

@if(session('status'))
    <x-alert type="status">{{ session('status') }}</x-alert>
@endif

@if($errors->any())
    <x-alert type="error">{{ $errors->first() }}</x-alert>
@endif

<div class="card">
    <form method="GET" action="{{ route('dashboard') }}">
        <div class="filters-grid">
            <div>
                <label for="date_from" class="field-label">Fecha desde</label>
                <input id="date_from" type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                <input type="hidden" name="quick_filter" value="{{ $quickFilter }}">
            </div>

            <div>
                <label for="date_to" class="field-label">Fecha hasta</label>
                <input id="date_to" type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>

            <div>
                <label for="manual_status" class="field-label">Validación manual</label>
                <select id="manual_status" name="manual_status" class="form-control">
                    <option value="all" @selected($reviewFilters['manual_status'] === 'all')>Todos</option>
                    <option value="pending" @selected($reviewFilters['manual_status'] === 'pending')>Pendientes</option>
                    <option value="correct" @selected($reviewFilters['manual_status'] === 'correct')>Correctos</option>
                    <option value="false_positive" @selected($reviewFilters['manual_status'] === 'false_positive')>Falsos positivos</option>
                </select>
            </div>

            <div>
                <label for="detected_status" class="field-label">Estado detectado</label>
                <select id="detected_status" name="detected_status" class="form-control">
                    <option value="all" @selected($reviewFilters['detected_status'] === 'all')>Todos</option>
                    <option value="compliant" @selected($reviewFilters['detected_status'] === 'compliant')>Cumple EPP</option>
                    <option value="non_compliant" @selected($reviewFilters['detected_status'] === 'non_compliant')>Incumple EPP</option>
                </select>
            </div>

            <div>
                <label for="camera" class="field-label">Cámara</label>
                <select id="camera" name="camera" class="form-control">
                    <option value="all" @selected($reviewFilters['camera'] === 'all')>Todas</option>
                    @foreach($reviewCameras as $camera)
                        <option value="{{ $camera }}" @selected($reviewFilters['camera'] === $camera)>
                            {{ dashboardCameraLabel($camera) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="zone" class="field-label">Lugar/zona</label>
                <select id="zone" name="zone" class="form-control">
                    <option value="all" @selected($reviewFilters['zone'] === 'all')>Todas</option>
                    @foreach($reviewZones as $zone)
                        <option value="{{ $zone }}" @selected($reviewFilters['zone'] === $zone)>
                            {{ $zone }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="review-filter-actions">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary fix-width-button">Limpiar filtros</a>
            </div>
        </div>

       
    </form>
</div>

<div class="page-header">
    <div>
        <h2>KPIs operativos</h2>
        <p class="topbar-subtitle">Calculados con eventos de cumplimiento e incumplimiento confirmado.</p>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card blue">
        <div class="kpi-icon blue" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12h4l3 8 4-16 3 8h4" />
            </svg>
        </div>
        <div class="kpi-value">{{ number_format($totalEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Total de eventos detectados</div>
    </div>

    <div class="kpi-card red">
        <div class="kpi-icon red" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 3 9 16H3L12 3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
        </div>
        <div class="kpi-value">{{ number_format($nonCompliantEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Incumplimientos detectados</div>
    </div>

    <div class="kpi-card orange">
        <div class="kpi-icon orange" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="8" />
                <path d="M12 8v5l3 2" />
                <path d="M9 2h6" />
            </svg>
        </div>
        <div class="kpi-value">{{ number_format($humanPendingEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Eventos pendientes de gestión</div>
    </div>

    <div class="kpi-card green">
        <div class="kpi-icon green" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.8 1.8 0 0 1-1 0C7.5 20.5 4 18 4 13V6l8-3 8 3v7Z" />
                <path d="m9 12 2 2 4-4" />
            </svg>
        </div>
        <div class="kpi-value">{{ number_format($humanResolvedEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Eventos gestionados y cerrados</div>
    </div>
</div>

<div class="page-header">
    <div>
        <h2>Validación manual de estados de cumplimientos</h2>
        <p class="topbar-subtitle">Cards mixtas de incumplimientos y cumplimientos observados.</p>
    </div>
</div>

<div class="quick-filter-tabs" role="navigation" aria-label="Filtros rápidos de validación">
    @foreach($quickFilters as $filterKey => $filterLabel)
        @php
            $quickFilterUrl = route('dashboard', array_merge(
                request()->except(['page', 'quick_filter']),
                ['quick_filter' => $filterKey]
            ));
        @endphp

        <a
            href="{{ $quickFilterUrl }}"
            class="quick-filter-tab {{ $quickFilter === $filterKey ? 'active' : '' }}"
            aria-current="{{ $quickFilter === $filterKey ? 'page' : 'false' }}"
        >
            {{ $filterLabel }}
        </a>
    @endforeach
</div>

<div class="review-metrics-grid">
    <div class="review-metric-card blue">
        <div class="review-metric-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="8" />
                <circle cx="12" cy="12" r="3" />
                <path d="M12 2v3" />
                <path d="M12 19v3" />
                <path d="M2 12h3" />
                <path d="M19 12h3" />
            </svg>
        </div>
        <div class="review-metric-value">{{ number_format($reviewMetrics['accuracy'], 1) }}%</div>
        <div class="review-metric-label">Precisión general</div>
    </div>

    <div class="review-metric-card orange">
        <div class="review-metric-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 13c0 5-3.5 7.5-7.5 8.8a1.8 1.8 0 0 1-1 0C7.5 20.5 4 18 4 13V6l8-3 8 3v7Z" />
                <path d="m9.5 9.5 5 5" />
                <path d="m14.5 9.5-5 5" />
            </svg>
        </div>
        <div class="review-metric-value">{{ number_format($reviewMetrics['false_positive_rate'], 1) }}%</div>
        <div class="review-metric-label">Falsos positivos</div>
    </div>

    <div class="review-metric-card yellow">
        <div class="review-metric-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 3h12" />
                <path d="M6 21h12" />
                <path d="M7 3c0 4 5 5 5 9s-5 5-5 9" />
                <path d="M17 3c0 4-5 5-5 9s5 5 5 9" />
            </svg>
        </div>
        <div class="review-metric-value">{{ number_format($reviewMetrics['pending'], 0, ',', '.') }}</div>
        <div class="review-metric-label">Pendientes de revisión</div>
    </div>

    <div class="review-metric-card green">
        <div class="review-metric-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <path d="m8 12 2.5 2.5L16 9" />
            </svg>
        </div>
        <div class="review-metric-value">{{ number_format($reviewMetrics['total_validated'], 0, ',', '.') }}</div>
        <div class="review-metric-label">Eventos validados</div>
    </div>
</div>

<div class="review-grid">
    @forelse($reviewEvents as $event)
        @include('events.partials.review-card', [
            'event' => $event,
            'validationRouteName' => 'dashboard.review.store',
            'validationStatuses' => ['correct', 'false_positive'],
        ])
    @empty
        <div class="empty-state-card">
            <h3 class="empty-state-title">Sin eventos disponibles</h3>
            <p class="empty-state-description">No hay eventos para validar con los filtros seleccionados.</p>
        </div>
    @endforelse
</div>

<div class="mt-16">
    @include('events.partials.review-pagination', ['events' => $reviewEvents])
</div>
@endsection
