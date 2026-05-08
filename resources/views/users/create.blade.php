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

    <div class="container">
        <div class="">
            <h1>Crear usuario</h1>
            <p>Registra una nueva cuenta con el rol correspondiente.</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="info-section" onsubmit="this.querySelector('button[type=submit]')?.classList.add('is-loading');">
            @csrf

            <div>
                <label for="name" class="field-label">Nombre completo</label>
                <input id="name" name="name" value="{{ old('name') }}" class="form-control input-gradient-focus" required>
                <p class="helper-text">Usa el nombre visible para identificar al usuario en el sistema.</p>
                @error('name') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="email" class="field-label">Correo electrónico</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control input-gradient-focus" required>
                <p class="helper-text">Este correo se usará para iniciar sesión.</p>
                @error('email') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="role" class="field-label">Rol</label>
                <select id="role" name="role" class="form-control input-gradient-focus" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', 'viewer') === $role)>
                            {{ $roleLabels[$role] ?? $role }}
                        </option>
                    @endforeach
                </select>
                <p class="helper-text">El rol define los módulos y acciones disponibles.</p>
                @error('role') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="password" class="field-label">Contraseña</label>
                <input id="password" name="password" type="password" class="form-control input-gradient-focus" required>
                <p class="helper-text">Debe cumplir las reglas de seguridad configuradas.</p>
                @error('password') <x-alert type="validation">{{ $message }}</x-alert> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="field-label">Confirmar contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control input-gradient-focus" required>
            </div>

            <label class="toolbar-left">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1'))>
                <span>Usuario activo</span>
            </label>

            <div class="toolbar-left">
                <button type="submit" class="btn btn-gradient-primary">Guardar</button>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
