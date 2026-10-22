@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center py-5">
            <h1 class="display-6 fw-semibold">Plataforma de Gestão de Demandas Tecnológicas</h1>
            <p class="lead text-muted mt-3">
                Rede de Apoio Tecnológico às Escolas — UFES/CCENS, Alegre-ES
            </p>

            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 mt-4">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Ir para o painel</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Entrar</a>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">Criar conta</a>
                @endauth
            </div>

            <p class="mt-4 mb-0">
                <a href="{{ route('publico.index') }}">Conheça os projetos de extensão e as vagas abertas para estudantes</a>
            </p>
        </div>
    </div>
</div>
@endsection
