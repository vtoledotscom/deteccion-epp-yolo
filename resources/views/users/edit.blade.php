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
    <div class="page-back">
        <a href="{{ route('users.index') }}" class="link-primary">Volver a usuarios</a>
    </div>

    <div class="card">
        <div class="card-header-gradient">
            <h1>Editar usuario</h1>
            <p>Actualiza los datos de acceso y estado de la cuenta.</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="info-section" onsubmit="this.querySelector('button[type=submit]')?.classList.add('is-loading');">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="field-label">Nombre completo</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control input-gradient-focus" required>
                <p class="helper-text">Nombre visible en menús, auditoría y acciones del sistema.</p>
                @error('name') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="email" class="field-label">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control input-gradient-focus" required>
                <p class="helper-text">Mantén este correo actualizado para el acceso del usuario.</p>
                @error('email') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="role" class="field-label">Rol</label>
                <select id="role" name="role" class="form-control input-gradient-focus" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                            {{ $roleLabels[$role] ?? $role }}
                        </option>
                    @endforeach
                </select>
                <p class="helper-text">Cambiar el rol ajusta los permisos efectivos.</p>
                @error('role') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="password" class="field-label">Nueva contraseña</label>
                <input id="password" name="password" type="password" class="form-control input-gradient-focus">
                <p class="helper-text">Déjalo en blanco para conservar la contraseña actual.</p>
                @error('password') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirmar nueva contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control input-gradient-focus">
            </div>

            <label class="toolbar-left">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $user->is_active))
                    @disabled(auth()->id() === $user->id)
                >
                <span>Usuario activo</span>
            </label>

            @if (auth()->id() === $user->id)
                <p class="topbar-subtitle">No puedes deshabilitar tu propio usuario.</p>
            @endif

            <div class="toolbar-left">
                <button type="submit" class="btn btn-gradient-primary">Actualizar</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
