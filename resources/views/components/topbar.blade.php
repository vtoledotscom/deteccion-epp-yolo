<div class="topbar-wrapper">
    <div>
        <h1 class="topbar-title">EPPA detección</h1>
        <p class="topbar-subtitle">Panel de Monitoreo Operacional</p>
    </div>

    <div class="topbar-actions">
        <nav class="topbar-menu">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('events.index') }}">Eventos</a>
            <a href="{{ route('events.open') }}">Eventos abiertos</a>
            <a href="{{ route('reports.index') }}">Reportes</a>
        </nav>

        <div class="topbar-user-box">
            <div class="avatar small">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
            </div>

            <div class="topbar-user-info">
                <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong>
                <small>{{ auth()->user()->email ?? '' }}</small>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">
                Salir
            </button>
        </form>
    </div>
</div>