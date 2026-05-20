<!-- estructura común de todas las páginas: cabecera, menú, estilos, pie, etc. -->

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Roig Arena')</title>
    <link rel="stylesheet" href="/css/site.css">
    @yield('page_styles')
</head>
<body class="@yield('body_class')">
    <header class="nav">
        <div class="container nav-inner">
            <div class="brand">Roig Arena</div>
            <nav class="links">
                <a class="link" href="{{ route('home', [], false) }}">Inicio</a>
                <a class="link" href="{{ route('eventos.index', [], false) }}">Eventos</a>
                @guest
                    <a class="link link-cta" href="{{ route('login', [], false) }}">
                        <svg width="25px" height="25px" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
<path d="M8 7C9.65685 7 11 5.65685 11 4C11 2.34315 9.65685 1 8 1C6.34315 1 5 2.34315 5 4C5 5.65685 6.34315 7 8 7Z" fill="#000000"/>
<path d="M14 12C14 10.3431 12.6569 9 11 9H5C3.34315 9 2 10.3431 2 12V15H14V12Z"/>
</svg>
                    </a>
                @endguest

                @auth
                    <a class="link" href="{{ route('dashboard', [], false) }}">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</a>
                @endauth

            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            @yield('content')
        </div>
    </main>

    @yield('page_scripts')
</body>
</html>
