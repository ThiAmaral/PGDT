@extends('layouts.app')

@section('titulo', 'Confirmar senha')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Confirme sua senha</h1>
                    <p class="text-muted small mb-4">Por segurança, confirme sua senha antes de continuar.</p>

                    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                            @error('password')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Confirmar</button>

                        <div class="text-center mt-3 small">
                            <a href="{{ route('password.request') }}">Esqueci minha senha</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
