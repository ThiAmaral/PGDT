@extends('layouts.app')

@section('titulo', 'Entrar')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Entrar</h1>
                    <p class="text-muted small mb-4">Use o e-mail e a senha da sua conta. O sistema reconhece o seu perfil automaticamente.</p>

                    @if (str_contains((string) session('url.intended'), '/oportunidades/'))
                        <div class="alert alert-info small">Entre com a sua conta de estudante para concluir a inscrição no sub-projeto.</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">Manter conectado</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Entrar</button>

                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-3 small">
                            <a href="{{ route('password.request') }}">Esqueci minha senha</a>
                            <a href="{{ route('register') }}">Ainda não tem conta? Criar conta</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
