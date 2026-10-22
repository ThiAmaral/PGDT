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
                            <h2 class="h5">Demandas novas</h2>
                            <p class="display-6 mb-2">{{ $totalDemandasNovas }}</p>
                            <a href="{{ route('demandas.index') }}" class="btn btn-primary btn-sm">Abrir a fila</a>
                        </div>
                    </div>
                </div>
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
                            <h2 class="h5">Candidaturas pendentes</h2>
                            <p class="display-6 mb-2">{{ $totalCandidaturasPendentes }}</p>
                            <a href="{{ route('subprojetos.index') }}" class="btn btn-primary btn-sm">Abrir sub-projetos</a>
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
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Projetos Base</h2>
                            <p class="text-muted">Projetos de extensão que recebem as demandas e seus coordenadores.</p>
                            <a href="{{ route('projetos-base.index') }}" class="btn btn-outline-primary btn-sm">Abrir projetos</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Cadastros de apoio</h2>
                            <p class="text-muted">Tipologias, categorias de demanda e prioridades.</p>
                            <a href="{{ route('admin.dominios.index', 'tipologias') }}" class="btn btn-outline-primary btn-sm">Abrir cadastros</a>
                        </div>
                    </div>
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Coordenador)
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Demandas novas</h2>
                            <p class="display-6 mb-2">{{ $totalDemandasNovas }}</p>
                            <p class="text-muted">Demandas dos seus projetos aguardando análise. Classifique e priorize cada uma.</p>
                            <a href="{{ route('demandas.index') }}" class="btn btn-primary btn-sm">Abrir a fila</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Candidaturas pendentes</h2>
                            <p class="display-6 mb-2">{{ $totalCandidaturasPendentes }}</p>
                            <p class="text-muted">Estudantes que querem entrar nas equipes dos seus sub-projetos.</p>
                            <a href="{{ route('subprojetos.index') }}" class="btn btn-primary btn-sm">Abrir sub-projetos</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Seus projetos de extensão</h2>
                            <p class="display-6 mb-2">{{ $totalMeusProjetos }}</p>
                            <p class="text-muted">Projetos Base que recebem as demandas das escolas.</p>
                            <a href="{{ route('projetos-base.index', ['meus' => 1]) }}" class="btn btn-outline-primary btn-sm">Ver meus projetos</a>
                            <a href="{{ route('projetos-base.create') }}" class="btn btn-outline-primary btn-sm">Novo Projeto Base</a>
                        </div>
                    </div>
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Demandante)
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-2">
                        <div>
                            <h2 class="h5 mb-1">Suas demandas</h2>
                            <p class="text-muted small mb-0">Escola/instituição: {{ $usuario->demandante?->escolaInstituicao?->nomeCompleto() }}</p>
                        </div>
                        <a href="{{ route('demandas.create') }}" class="btn btn-primary btn-sm">Nova demanda</a>
                    </div>

                    @forelse ($minhasDemandas as $demanda)
                        <a href="{{ route('demandas.show', $demanda) }}" class="d-flex justify-content-between align-items-center gap-2 border-top py-2 text-body text-decoration-none">
                            <span class="text-break">{{ $demanda->titulo }}</span>
                            <span class="badge text-bg-{{ $demanda->status->cor() }}">{{ $demanda->status->rotulo() }}</span>
                        </a>
                    @empty
                        <p class="text-muted mb-0">Você ainda não abriu nenhuma demanda. Precisa de apoio tecnológico? Clique em "Nova demanda".</p>
                    @endforelse

                    @if ($minhasDemandas->isNotEmpty())
                        <a href="{{ route('demandas.index') }}" class="btn btn-outline-primary btn-sm mt-2">Ver todas</a>
                    @endif
                </div>
            </div>
            @break

        @case(\App\Enums\Perfil::Estudante)
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Oportunidades</h2>
                            <p class="display-6 mb-2">{{ $totalOportunidades }}</p>
                            <p class="text-muted">Sub-projetos com vagas abertas para estudantes extensionistas.</p>
                            <a href="{{ route('oportunidades.index') }}" class="btn btn-primary btn-sm">Ver oportunidades</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Minhas equipes</h2>
                            @forelse ($minhasEquipes as $subprojeto)
                                <a href="{{ route('subprojetos.show', $subprojeto) }}" class="d-flex justify-content-between align-items-center gap-2 border-top py-2 text-body text-decoration-none">
                                    <span class="text-break">{{ $subprojeto->titulo }}</span>
                                    <span class="badge text-bg-{{ $subprojeto->solicitacao->status->cor() }}">{{ $subprojeto->solicitacao->status->rotulo() }}</span>
                                </a>
                            @empty
                                <p class="text-muted">Você ainda não faz parte de nenhuma equipe. Candidate-se a um sub-projeto em "Oportunidades".</p>
                            @endforelse
                            <a href="{{ route('subprojetos.index') }}" class="btn btn-outline-primary btn-sm mt-2">Meus sub-projetos e candidaturas</a>
                        </div>
                    </div>
                </div>
            </div>
            @break
    @endswitch
</div>
@endsection
