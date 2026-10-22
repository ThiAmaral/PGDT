@extends('layouts.app')

@section('titulo', $subprojeto->titulo)

@section('content')
<div class="container">
    <div class="mb-2">
        <a href="{{ route('oportunidades.index') }}" class="small">&larr; Oportunidades</a>
    </div>

    @error('candidatura')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <span class="badge text-bg-{{ $subprojeto->modalidade->cor() }} mb-2">{{ $subprojeto->modalidade->rotulo() }}</span>
                    <h1 class="h3 mb-1">{{ $subprojeto->titulo }}</h1>
                    <p class="text-muted mb-3">Projeto de extensão: {{ $subprojeto->solicitacao->projetoBase->titulo }}</p>

                    <h2 class="h6 text-muted">Escopo</h2>
                    <p style="white-space: pre-line">{{ $subprojeto->escopo }}</p>

                    <h2 class="h6 text-muted">Sobre o projeto de extensão</h2>
                    <p class="mb-0" style="white-space: pre-line">{{ $subprojeto->solicitacao->projetoBase->descricao }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Coordenação</dt>
                        <dd>{{ $subprojeto->coordenador->nome }}</dd>
                        <dt class="small text-muted">Período</dt>
                        <dd>{{ $subprojeto->periodo() }}</dd>
                        <dt class="small text-muted">Carga horária estimada</dt>
                        <dd>{{ $subprojeto->carga_horaria_estimada }} horas</dd>
                        <dt class="small text-muted">Vagas</dt>
                        <dd class="mb-0">{{ $subprojeto->vagasLivres() }} livre(s) de {{ $subprojeto->vagas }}</dd>
                    </dl>
                </div>
            </div>

            {{-- RF10: candidatura com um clique. --}}
            <div class="card shadow-sm mb-3" id="candidatura">
                <div class="card-body">
                    <h2 class="h6 text-muted">Sua candidatura</h2>

                    @if ($candidatura)
                        <p class="mb-2">
                            <span class="badge text-bg-{{ $candidatura->status->cor() }}">{{ $candidatura->status->rotulo() }}</span>
                            <span class="small text-muted ms-1">enviada em {{ $candidatura->created_at->format('d/m/Y') }}</span>
                        </p>
                    @endif

                    @if ($impedimento === null)
                        <p class="small text-muted">Ao confirmar, a coordenação recebe a sua candidatura e decide se você entra na equipe.</p>
                        <form method="POST" action="{{ route('candidaturas.store', $subprojeto) }}" data-enviando="Enviando...">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">Confirmar candidatura</button>
                        </form>
                    @else
                        <p class="small mb-0">{{ $impedimento }}</p>
                    @endif

                    @if ($candidatura?->estaPendente())
                        <form method="POST" action="{{ route('subprojetos.candidaturas.cancelar', [$subprojeto, $candidatura]) }}" class="mt-2" data-confirmar="Cancelar a sua candidatura a este sub-projeto?">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Cancelar candidatura</button>
                        </form>
                    @endif

                    @can('view', $subprojeto)
                        <a href="{{ route('subprojetos.show', $subprojeto) }}" class="btn btn-sm btn-outline-primary w-100 mt-2">Abrir o sub-projeto</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
