@extends('layouts.app')

@php
    $roleLabels = [
        'admin' => 'Administrador',
        'supervisor' => 'Supervisor',
        'operator' => 'Operador',
        'viewer' => 'Visualizador',
    ];
@endphp

@section('content')
    <style>
        .users-table th,
        .users-table td {
            padding: 11px 12px;
            vertical-align: middle;
        }

        .user-identity {
            min-width: 220px;
        }

        .user-name {
            display: block;
            color: var(--text);
            font-weight: 800;
            line-height: 1.25;
            font-size: 16px;
        }

        .user-email {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            word-break: break-word;
        }

        .role-label {
            color: var(--text);
            font-size: 16px;
            font-weight: 700;
            white-space: nowrap;
        }

        .users-table .badge {
            padding: 4px 8px;
            font-size: 16px;
            line-height: 1.2;
        }

        .date-stack {
            display: inline-flex;
            flex-direction: column;
            gap: 2px;
            color: var(--text);
            font-size: 16px;
            font-weight: 700;
            line-height: 1.25;
            white-space: nowrap;
        }

        .date-stack .time {
            color: var(--muted);
            font-size: 16px;
            font-weight: 600;
        }

        .user-actions {
            gap: 20px;
            flex-wrap: nowrap;
        }

        .user-actions .link-primary {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .user-actions .btn {
            width: auto;
            min-width: 0;
            max-width: none;
            height: 34px;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
            white-space: nowrap;
        }

        .user-actions .btn-danger-outline {
            border-color: #fecaca;
            color: #b91c1c;
            background: #fff;
        }

        .user-actions .btn-danger-outline:hover {
            border-color: #ef4444;
            color: #991b1b;
            background: #fff7f7;
        }

        @media (max-width: 900px) {
            .user-actions {
                flex-wrap: wrap;
            }

            .user-actions .btn,
            .user-actions .link-primary {
                flex: 1 1 auto;
                justify-content: center;
            }
        }
    </style>

    <div class="page-header">
        <div>
            <h1>Gestión de usuarios</h1>
            <p class="topbar-subtitle">Administración de cuentas y accesos del sistema.</p>
        </div>

        <a href="{{ route('users.create') }}" class="btn btn-primary">Crear usuario</a>
    </div>

    @if (session('status'))
        <div class="alert-box">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-box">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <form method="GET" action="{{ route('users.index') }}" class="filters-inline" onsubmit="this.querySelector('button[type=submit]')?.classList.add('is-loading');">
            <div class="inline-field">
                <label for="search" class="field-label">Buscar</label>
                <input
                    id="search"
                    name="search"
                    type="search"
                    value="{{ $search }}"
                    class="form-control search-input"
                    placeholder="Nombre, email o rol"
                >
            </div>

            <button type="submit" class="btn btn-primary">Buscar</button>

            @if ($search !== '')
                <a href="{{ route('users.index') }}" class="btn btn-secondary fix-width-button">Limpiar filtros</a>
            @endif
        </form>

        <div class="table-wrapper">
            <table class="data-table users-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="user-identity">
                                    <span class="user-name">{{ $user->name }}</span>
                                    <span class="user-email">{{ $user->email }}</span>
                                </div>
                            </td>
                            <td><span class="role-label">{{ $roleLabels[$user->role] ?? $user->role }}</span></td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge success">Activo</span>
                                @else
                                    <span class="badge danger">Deshabilitado</span>
                                @endif
                            </td>
                            <td>
                                <span class="date-stack">
                                    <span>{{ $user->created_at?->format('d-m-Y') }}</span>
                                    <span class="time">{{ $user->created_at?->format('H:i') }}</span>
                                </span>
                            </td>
                            <td>
                                <div class="toolbar-left user-actions">
                                    <a href="{{ route('users.edit', $user) }}" class="link-primary">Editar</a>

                                    @if ($user->is_active)
                                        <form method="POST" action="{{ route('users.disable', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="btn btn-secondary"
                                                @disabled(auth()->id() === $user->id)
                                            >
                                                Deshabilitar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('users.activate', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-secondary">Activar</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('users.destroy', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-secondary btn-danger-outline"
                                            onclick="return confirm('¿Eliminar definitivamente este usuario? Esta acción no se puede deshacer.')"
                                            @disabled(auth()->id() === $user->id)
                                        >
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state-card">
                                    <h3 class="empty-state-title">Sin usuarios encontrados</h3>
                                    <p class="empty-state-description">Revisa el texto de búsqueda o crea una nueva cuenta.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('vendor.pagination.epp') }}
    </div>
@endsection
