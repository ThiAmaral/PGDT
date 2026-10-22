@extends('layouts.app')

@section('titulo', 'Projetos Base')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Projetos Base</h1>
            <p class="text-muted small mb-0">Projetos de extensão que recebem as demandas das escolas e instituições.</p>
        </div>
        @can('create', \App\Models\ProjetoBase::class)
            <a href="{{ route('projetos-base.create') }}" class="btn btn-primary">Novo Projeto Base</a>
        @endcan
    </div>

    @if ($semCoordenador > 0)
        <div class="alert alert-warning">
            {{ $semCoordenador === 1 ? 'Um projeto está' : "{$semCoordenador} projetos estão" }} sem coordenador responsável.
            Abra o projeto e clique em "Editar" para definir quem coordena.
        </div>
    @endif

    <form method="GET" action="{{ route('projetos-base.index') }}" class="card card-body shadow-sm mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="busca" class="form-label small">Título</label>
                <input id="busca" type="search" name="busca" class="form-control" value="{{ $filtros['busca'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="tipologia" class="form-label small">Tipologia</label>
                <select id="tipologia" name="tipologia" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($tipologias as $tipologia)
                        <option value="{{ $tipologia->id }}" @selected((string) ($filtros['tipologia'] ?? '') === (string) $tipologia->id)>{{ $tipologia->nome }}</option>
                    @endforeach
                </select>
            </div>
            @if ($gerencia)
                <div class="col-6 col-md-2">
                    <label for="situacao" class="form-label small">Situação</label>
                    <select id="situacao" name="situacao" class="form-select">
                        <option value="">Todos</option>
                        <option value="ativos" @selected(($filtros['situacao'] ?? '') === 'ativos')>Ativos</option>
                        <option value="inativos" @selected(($filtros['situacao'] ?? '') === 'inativos')>Inativos</option>
                    </select>
                </div>
            @endif
            <div class="col-md-3 d-flex flex-wrap gap-2 align-items-center">
                @if (auth()->user()->temPerfil(\App\Enums\Perfil::Coordenador))
                    <div class="form-check w-100 mb-1">
                        <input id="meus" type="checkbox" name="meus" value="1" class="form-check-input" @checked($filtros['meus'] ?? false)>
                        <label for="meus" class="form-check-label small">Só os que eu coordeno</label>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                <a href="{{ route('projetos-base.index') }}" class="btn btn-outline-secondary" title="Limpar filtros">Limpar</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Título</th>
                        <th class="d-none d-md-table-cell">Tipologia</th>
                        <th class="d-none d-md-table-cell">Coordenador</th>
                        <th class="d-none d-lg-table-cell">Período</th>
                        @if ($gerencia)
                            <th>Situação</th>
                        @endif
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($projetos as $item)
                        <tr>
                            <td>
                                <a href="{{ route('projetos-base.show', $item) }}" class="text-body">{{ $item->titulo }}</a>
                                <div class="small text-muted d-md-none">{{ $item->tipologia->nome }} · {{ $item->coordenador?->nome ?? 'Sem coordenador' }}</div>
                            </td>
                            <td class="d-none d-md-table-cell">{{ $item->tipologia->nome }}</td>
                            <td class="d-none d-md-table-cell">
                                @if ($item->coordenador)
                                    {{ $item->coordenador->nome }}
                                @else
                                    <span class="text-warning-emphasis">A definir</span>
                                @endif
                            </td>
                            <td class="d-none d-lg-table-cell small">{{ $item->periodo() }}</td>
                            @if ($gerencia)
                                <td>
                                    <span class="badge text-bg-{{ $item->ativo ? 'success' : 'secondary' }}">{{ $item->ativo ? 'Ativo' : 'Inativo' }}</span>
                                    @unless ($item->publico)
                                        <div class="small text-muted">oculto da página pública</div>
                                    @endunless
                                </td>
                            @endif
                            <td class="text-end text-nowrap">
                                @can('update', $item)
                                    <a href="{{ route('projetos-base.edit', $item) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                @endcan
                                <a href="{{ route('projetos-base.show', $item) }}" class="btn btn-sm btn-outline-primary">Detalhes</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhum Projeto Base encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $projetos->links() }}
    </div>
</div>
@endsection
