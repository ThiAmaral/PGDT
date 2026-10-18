@extends('layouts.app')

@section('titulo', 'Cadastro em análise')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm text-center">
                <div class="card-body p-5">
                    <div class="display-5 mb-3" aria-hidden="true">⏳</div>
                    <h1 class="h4">Cadastro em análise</h1>
                    <p class="mt-3">
                        Olá, {{ $usuario->nome }}. Recebemos o seu cadastro como
                        <strong>{{ $usuario->perfil->rotulo() }}</strong>.
                    </p>
                    <p class="text-muted">
                        O administrador vai conferir os seus dados. Você recebe um e-mail em
                        <strong>{{ $usuario->email }}</strong> quando o cadastro for aprovado.
                    </p>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Sair</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
