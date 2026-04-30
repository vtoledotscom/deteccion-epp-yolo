<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="epp-login-page">
        <div class="brand-top-bar"></div>
        <div class="brand-bottom-bar"></div>
        <div class="epp-login-bg-shape epp-login-bg-shape-left"></div>
        <div class="epp-login-bg-shape epp-login-bg-shape-right"></div>

        <div class="epp-login-card">
            <div class="epp-login-brand">
                <img src="{{ asset('images/logo-tscom.svg') }}" alt="TSCOM" class="epp-login-logo">

                <h1>EPPA detector</h1>
                <p>Ingresa tus credenciales de acceso</p>
            </div>

            <x-auth-session-status class="epp-login-status" :status="session('status')" />

            <form method="POST" action="{{ route('login.store') }}" class="epp-login-form">
                @csrf

                <div class="epp-login-field">
                    <label for="email">Correo electrónico</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="Ingresa tu correo"
                    >

                    @error('email')
                        <span class="epp-login-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="epp-login-field">
                    <label for="password">Contraseña</label>

                    <div class="epp-password-wrapper">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Ingresa tu contraseña"
                        >

                        <button type="button" class="epp-password-toggle" onclick="toggleLoginPassword()">
                            👁
                        </button>
                    </div>

                    @error('password')
                        <span class="epp-login-error">{{ $message }}</span>
                    @enderror
                </div>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="epp-login-link" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif


                <flux:button type="submit" class="btn-gradient-primary">
                    Iniciar sesión
                </flux:button>
            </form>
        </div>
    </div>

    <script>
        function toggleLoginPassword() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</x-layouts::auth>
