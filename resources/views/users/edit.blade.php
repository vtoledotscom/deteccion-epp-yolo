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
        <div class="card-header-column">
            <h1>Editar usuario</h1>
            <p>Actualiza los datos de acceso y estado de la cuenta.</p>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="info-section">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="field-label">Nombre</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                @error('name') <span class="epp-login-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email" class="field-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                @error('email') <span class="epp-login-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="role" class="field-label">Rol</label>
                <select id="role" name="role" class="form-control" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                            {{ $roleLabels[$role] ?? $role }}
                        </option>
                    @endforeach
                </select>
                @error('role') <span class="epp-login-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password" class="field-label">Nueva contraseña</label>
                <input id="password" name="password" type="password" class="form-control">
                @error('password') <span class="epp-login-error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirmar nueva contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control">
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
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
