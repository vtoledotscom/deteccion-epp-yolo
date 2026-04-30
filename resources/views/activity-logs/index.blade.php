@extends('layouts.app')

@php
    $roleLabels = [
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operator' => 'Operador',
        'viewer' => 'Visualizador',
    ];

    $actionLabels = [
        'login' => 'Login exitoso',
        'logout' => 'Logout',
        'unauthorized_access' => 'Acceso no autorizado',
        'view_dashboard' => 'Vista dashboard',
        'view_events' => 'Vista eventos',
        'view_event_detail' => 'Detalle evento',
        'download_event_pdf' => 'PDF evento',
        'download_csv' => 'Descarga CSV',
        'download_pdf' => 'Descarga PDF',
        'download_evidence' => 'Evidencia',
        'view_reports' => 'Vista reportes',
        'create_user' => 'Creación usuario',
        'update_user' => 'Edición usuario',
        'activate_user' => 'Activación usuario',
        'disable_user' => 'Deshabilitación usuario',
        'delete_user' => 'Eliminación usuario',
    ];
@endphp

@section('content')
    <div class="page-header">
        <div>
            <h1>Auditoría</h1>
            <p class="topbar-subtitle">Actividad registrada de usuarios según jerarquía de acceso.</p>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="filters-grid">
            <div>
                <label for="user" class="field-label">Usuario</label>
                <input id="user" name="user" value="{{ $filters['user'] }}" class="form-control" placeholder="Nombre o email">
            </div>

            <div>
                <label for="role" class="field-label">Rol</label>
                <select id="role" name="role" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected($filters['role'] === $role)>
                            {{ $roleLabels[$role] ?? $role }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="action" class="field-label">Acción</label>
                <select id="action" name="action" class="form-control">
                    <option value="">Todas</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected($filters['action'] === $action)>
                            {{ $actionLabels[$action] ?? $action }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="module" class="field-label">Módulo</label>
                <select id="module" name="module" class="form-control">
                    <option value="">Todos</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}" @selected($filters['module'] === $module)>
                            {{ ucfirst($module) }}
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

            <div>
                <label for="ip" class="field-label">IP</label>
                <input id="ip" name="ip" value="{{ $filters['ip'] }}" class="form-control" placeholder="IP">
            </div>

            <div class="report-actions-cell">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </form>

        <div class="toolbar-left">
            <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">Limpiar filtros</a>
            <a href="{{ route('activity-logs.export.csv', request()->query()) }}" class="btn btn-primary">Exportar CSV</a>
        </div>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Descripción</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d-m-Y H:i:s') }}</td>
                            <td>
                                <strong>{{ $log->user_name ?? 'N/D' }}</strong><br>
                                <small>{{ $log->user_email ?? '' }}</small>
                            </td>
                            <td>{{ $roleLabels[$log->user_role] ?? $log->user_role }}</td>
                            <td><span class="badge success">{{ $actionLabels[$log->action] ?? $log->action }}</span></td>
                            <td>{{ ucfirst($log->module) }}</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">No hay registros para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-16">
            {{ $logs->links('vendor.livewire.epp-pagination') }}
        </div>
    </div>
@endsection
