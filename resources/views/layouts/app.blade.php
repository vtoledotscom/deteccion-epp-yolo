<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'EPPA detección' }}</title>
    <link rel="icon" href="{{ asset('images/favicon2.ico') }}" sizes="any" type="image/x-icon">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="app-body">
    <div class="app-shell">
        <aside class="app-sidebar">
            @include('components.sidebar')
        </aside>

        <div class="app-main">
            <main class="app-content">
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>