@php
    $currentRoute = request()->route()?->getName();
@endphp

<div class="sidebar-wrapper">
    <div class="sidebar-logo">
        <div class="logo-mark">m</div>
        <div class="logo-text">
            <strong>TSCOM</strong>
        </div>
    </div>

    <div class="sidebar-section-title">MENÚ PRINCIPAL</div>

    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}"
           class="sidebar-link {{ $currentRoute === 'dashboard' ? 'active' : '' }}">
            <span>Dashboard</span>
        </a>

        <a href="{{ route('events.index') }}"
           class="sidebar-link {{ $currentRoute === 'events.index' ? 'active' : '' }}">
            <span>Eventos</span>
        </a>

        {{-- <a href="{{ route('events.open') }}"
           class="sidebar-link {{ $currentRoute === 'events.open' ? 'active' : '' }}">
            <span>Eventos abiertos</span>
            <span class="sidebar-badge">32</span>
        </a> --}}

        <a href="{{ route('reports.index') }}"
           class="sidebar-link {{ $currentRoute === 'reports.index' ? 'active' : '' }}">
            <span>Reportes</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">JS</div>
            <div>
                <div class="user-name">Juan Salgado</div>
                <div class="user-role">Supervisor Operacional</div>
            </div>
        </div>

        <div class="sidebar-support">
            <div>Soporte TSCOM</div>
            <small>+56 2 2912 7530</small>
        </div>
    </div>
</div>