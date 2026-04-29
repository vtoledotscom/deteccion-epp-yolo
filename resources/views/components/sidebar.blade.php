@php
    use App\Models\EppEvent;

    $currentRoute = request()->route()?->getName();
    $user = auth()->user();
    $openEventsCount = EppEvent::query()
        ->where('event_type', 'violation_started')
        ->whereNull('resolved_by_event_id')
        ->count();
    $roleLabel = match ($user?->role) {
        'admin' => 'Administrador',
        'viewer' => 'Visualizador',
        default => 'Usuario',
    };
@endphp

<div class="sidebar-wrapper">
    <div class="sidebar-logo">
        <div class="logo-mark"><img src="{{ asset('images/logo-tscom.svg') }}" alt="logo-tscom" /></div>
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

        <a href="{{ route('events.open') }}"
           class="sidebar-link {{ $currentRoute === 'events.open' ? 'active' : '' }}">
            <span>Eventos abiertos</span>
            <span class="sidebar-badge">{{ number_format($openEventsCount, 0, ',', '.') }}</span>
        </a>

        <a href="{{ route('reports.index') }}"
           class="sidebar-link {{ $currentRoute === 'reports.index' ? 'active' : '' }}">
            <span>Reportes</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">{{ $user?->initials() ?? 'U' }}</div>
            <div>
                <div class="user-name">{{ $user?->name ?? 'Usuario' }}</div>
                <div class="user-role">{{ $roleLabel }}</div>
            </div>
        </div>

        <div class="sidebar-support">
            <div>Soporte TSCOM</div>
            <small>+56 2 2912 7530</small>
        </div>
    </div>
</div>
