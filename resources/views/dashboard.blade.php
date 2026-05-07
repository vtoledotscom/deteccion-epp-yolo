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
    <div class="alert-box">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert-box">{{ $errors->first() }}</div>
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
    <div class="kpi-card">
        <div class="kpi-icon blue"></div>
        <div class="kpi-value">{{ number_format($totalEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Total de eventos detectados</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon red"></div>
        <div class="kpi-value">{{ number_format($nonCompliantEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Incumplimientos detectados</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon orange"></div>
        <div class="kpi-value">{{ number_format($humanPendingEvents, 0, ',', '.') }}</div>
        <div class="kpi-label">Eventos pendientes de gestión</div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon green"></div>
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
    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($reviewMetrics['accuracy'], 1) }}%</div>
        <div class="review-metric-label">Precisión general</div>
    </div>

    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($reviewMetrics['false_positive_rate'], 1) }}%</div>
        <div class="review-metric-label">Falsos positivos</div>
    </div>

    <div class="review-metric-card">
        <div class="review-metric-value">{{ number_format($reviewMetrics['pending'], 0, ',', '.') }}</div>
        <div class="review-metric-label">Pendientes de revisión</div>
    </div>

    <div class="review-metric-card">
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
