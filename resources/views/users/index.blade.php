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
    <div class="page-header">
        <div>
            <h1>Usuarios</h1>
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
        <form method="GET" action="{{ route('users.index') }}" class="filters-inline">
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
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Limpiar</a>
            @endif
        </form>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $roleLabels[$user->role] ?? $user->role }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge success">Activo</span>
                                @else
                                    <span class="badge danger">Deshabilitado</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at?->format('d-m-Y H:i') }}</td>
                            <td>
                                <div class="toolbar-left">
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
                                            class="btn btn-secondary"
                                            onclick="return confirm('¿Eliminar este usuario?')"
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
                            <td colspan="6" class="empty-state">No se encontraron usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('vendor.livewire.epp-pagination') }}
    </div>
@endsection
