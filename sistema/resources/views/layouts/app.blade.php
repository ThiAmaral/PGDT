<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection('titulo')@yield('titulo') — @endif{{ config('app.name', 'PGDT') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div id="app" class="d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-semibold" href="{{ auth()->check() ? route('home') : route('inicio') }}">
                    {{ config('app.name', 'PGDT') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuPrincipal">
                    {{-- Menu por perfil: cada perfil vê só os seus itens. --}}
                    <ul class="navbar-nav me-auto">
                        @auth
                            @if (auth()->user()->estaAtivo())
                                <li class="nav-item">
                                    <a class="nav-link @if (request()->routeIs('home')) active @endif" href="{{ route('home') }}">Painel</a>
                                </li>
                                @switch(auth()->user()->perfil)
                                    @case(\App\Enums\Perfil::Admin)
                                        <li class="nav-item">
                                            <a class="nav-link @if (request()->routeIs('admin.usuarios.*')) active @endif" href="{{ route('admin.usuarios.index') }}">Usuários</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link @if (request()->routeIs('admin.convites.*')) active @endif" href="{{ route('admin.convites.index') }}">Convites</a>
                                        </li>
                                        @break
                                @endswitch
                            @endif
                        @endauth
                    </ul>

                    <ul class="navbar-nav ms-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Entrar</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Criar conta</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a id="menuUsuario" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ auth()->user()->nome }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="menuUsuario">
                                    <span class="dropdown-item-text small text-muted">{{ auth()->user()->perfil->rotulo() }}</span>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Sair
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4 flex-grow-1">
            <div class="container">
                @if (session('sucesso'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('sucesso') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                @endif
                @if (session('status'))
                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                @endif
            </div>

            @yield('content')
        </main>

        <footer class="py-3 border-top bg-white">
            <div class="container small text-muted text-center">
                Rede de Apoio Tecnológico às Escolas — UFES/CCENS, Alegre-ES
            </div>
        </footer>
    </div>
</body>
</html>
