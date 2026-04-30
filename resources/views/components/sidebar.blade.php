@php
    use App\Models\EppEvent;

    $currentRoute = request()->route()?->getName();
    $user = auth()->user();
    $openEventsCount = EppEvent::query()
        ->where('human_review_status', 'pending')
        ->where('status', 'non_compliant')
        ->count();
    $closedEventsCount = EppEvent::query()
        ->where('human_review_status', 'resolved')
        ->where('status', 'non_compliant')
        ->count();
    $roleLabel = match ($user?->role) {
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operator' => 'Operador',
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
        @if($user?->hasPermission('view_dashboard'))
            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ $currentRoute === 'dashboard' ? 'active' : '' }}">
                <span>Dashboard</span>
            </a>
        @endif

        @if($user?->hasPermission('view_events'))
            <a href="{{ route('events.index') }}"
               class="sidebar-link {{ $currentRoute === 'events.index' ? 'active' : '' }}">
                <span>Eventos</span>
            </a>
        @endif

        @if($user?->hasPermission('view_open_events'))
            <a href="{{ route('events.open') }}"
               class="sidebar-link {{ str_starts_with((string) $currentRoute, 'events.open') ? 'active' : '' }}">
                <span>Eventos abiertos</span>
                <span class="sidebar-badge">{{ number_format($openEventsCount, 0, ',', '.') }}</span>
            </a>

            <a href="{{ route('events.closed') }}"
               class="sidebar-link {{ str_starts_with((string) $currentRoute, 'events.closed') ? 'active' : '' }}">
                <span>Eventos cerrados</span>
                <span class="sidebar-badge">{{ number_format($closedEventsCount, 0, ',', '.') }}</span>
            </a>
        @endif

        @if($user?->hasPermission('view_reports'))
            <a href="{{ route('reports.index') }}"
               class="sidebar-link {{ $currentRoute === 'reports.index' ? 'active' : '' }}">
                <span>Reportes</span>
            </a>
        @endif

        @if($user?->hasPermission('manage_users'))
            <a href="{{ route('users.index') }}"
               class="sidebar-link {{ str_starts_with((string) $currentRoute, 'users.') ? 'active' : '' }}">
                <span>Usuarios</span>
            </a>
        @endif

        @if($user?->hasPermission('view_user_activity_logs'))
            <a href="{{ route('activity-logs.index') }}"
               class="sidebar-link {{ str_starts_with((string) $currentRoute, 'activity-logs.') ? 'active' : '' }}">
                <span>Auditoría</span>
            </a>
        @endif
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
