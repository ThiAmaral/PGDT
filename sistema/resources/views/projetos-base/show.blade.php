@extends('layouts.app')

@section('titulo', $projeto->titulo)

@section('content')
<div class="container">
    <div class="mb-2">
        <a href="{{ route('projetos-base.index') }}" class="small">&larr; Projetos Base</a>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $projeto->titulo }}</h1>
            <span class="badge text-bg-light border">{{ $projeto->tipologia->nome }}</span>
            @can('create', \App\Models\ProjetoBase::class)
                <span class="badge text-bg-{{ $projeto->ativo ? 'success' : 'secondary' }}">{{ $projeto->ativo ? 'Ativo' : 'Inativo' }}</span>
                @unless ($projeto->publico)
                    <span class="badge text-bg-warning">Oculto da página pública</span>
                @endunless
            @endcan
        </div>
        @can('update', $projeto)
            <a href="{{ route('projetos-base.edit', $projeto) }}" class="btn btn-outline-primary">Editar</a>
        @endcan
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-muted">Descrição</h2>
                    <p style="white-space: pre-line">{{ $projeto->descricao }}</p>

                    <h2 class="h6 text-muted">Escopo preliminar</h2>
                    @if ($projeto->escopo_preliminar)
                        <p class="mb-0" style="white-space: pre-line">{{ $projeto->escopo_preliminar }}</p>
                    @else
                        <p class="text-muted mb-0">Não informado.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Coordenador responsável</dt>
                        <dd>
                            @if ($projeto->coordenador)
                                {{ $projeto->coordenador->nome }}
                                @if ($projeto->coordenador->coordenador?->departamento)
                                    <div class="small text-muted">{{ $projeto->coordenador->coordenador->departamento }}</div>
                                @endif
                            @else
                                <span class="text-warning-emphasis">A definir pelo administrador</span>
                            @endif
                        </dd>

                        <dt class="small text-muted">Período</dt>
                        <dd>{{ $projeto->periodo() }}</dd>

                        <dt class="small text-muted">Tipologia</dt>
                        <dd class="mb-0">{{ $projeto->tipologia->nome }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
