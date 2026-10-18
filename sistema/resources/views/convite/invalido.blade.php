@extends('layouts.app')

@section('titulo', 'Convite inválido')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm text-center">
                <div class="card-body p-5">
                    <h1 class="h4">Convite inválido ou expirado</h1>
                    <p class="text-muted mt-3">
                        Este link já foi usado, foi cancelado ou passou do prazo de validade.
                        Peça um novo convite ao administrador do sistema.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary mt-2">Ir para o login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
