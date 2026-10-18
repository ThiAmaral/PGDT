@extends('layouts.app')

@section('titulo', 'Painel')

@section('content')
<div class="container">
    <h1 class="h3 mb-1">Olá, {{ $usuario->nome }}</h1>
    <p class="text-muted mb-4">Perfil: {{ $usuario->perfil->rotulo() }}</p>

    @switch($usuario->perfil)
        @case(\App\Enums\Perfil::Admin)
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Cadastros pendentes</h2>
                            <p class="display-6 mb-2">{{ $totalPendentes }}</p>
                            <a href="{{ route('admin.usuarios.index', ['status' => 'pendente']) }}" class="btn btn-primary btn-sm">Analisar cadastros</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Usuários</h2>
                            <p class="text-muted">Aprovar, alterar status e trocar o perfil dos usuários.</p>
                            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-outline-primary btn-sm">Abrir usuários</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Convidar coordenador</h2>
                            <p class="text-muted">Envie um convite por e-mail para um Coordenador projeto de extensão.</p>
                            <a href="{{ route('admin.convites.index') }}" class="btn btn-outline-primary btn-sm">Abrir convites</a>
                        </div>
                    </div>
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Coordenador)
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Seus projetos de extensão</h2>
                    <p class="text-muted mb-0">Em breve você poderá cadastrar Projetos Base, fazer a triagem das demandas e montar as equipes.</p>
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Demandante)
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Suas demandas</h2>
                    <p class="text-muted mb-0">
                        Escola/instituição: {{ $usuario->demandante?->escolaInstituicao?->nomeCompleto() }}.<br>
                        Em breve você poderá abrir solicitações de apoio tecnológico e acompanhar o andamento.
                    </p>
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Estudante)
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Oportunidades</h2>
                    <p class="text-muted mb-0">Em breve você poderá ver os sub-projetos com vagas abertas e se candidatar.</p>
                </div>
            </div>
            @break
    @endswitch
</div>
@endsection
