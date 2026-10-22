@extends('layouts.app')

@php($tituloPagina = $ehDemandante ? 'Minhas demandas' : 'Demandas')

@section('titulo', $tituloPagina)

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">{{ $tituloPagina }}</h1>
            <p class="text-muted small mb-0">
                @if ($ehDemandante)
                    Solicitações de apoio tecnológico que você abriu e o andamento de cada uma.
                @elseif (auth()->user()->temPerfil(\App\Enums\Perfil::Admin))
                    Todas as demandas. A fila abre nas que ainda não foram analisadas.
                @else
                    Demandas dos projetos que você coordena. A fila abre nas que ainda não foram analisadas.
                @endif
            </p>
        </div>
        @can('create', \App\Models\Solicitacao::class)
            <a href="{{ route('demandas.create') }}" class="btn btn-primary">Nova demanda</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('demandas.index') }}" class="card card-body shadow-sm mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-{{ $ehDemandante ? 6 : 3 }}">
                <label for="busca" class="form-label small">Título</label>
                <input id="busca" type="search" name="busca" class="form-control" value="{{ $filtros['busca'] ?? '' }}">
            </div>
            <div class="col-6 col-md-{{ $ehDemandante ? 3 : 2 }}">
                <label for="status" class="form-label small">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\StatusDemanda::cases() as $status)
                        <option value="{{ $status->value }}" @selected(($filtros['status'] ?? '') === $status->value)>{{ $status->rotulo() }}</option>
                    @endforeach
                </select>
            </div>
            @unless ($ehDemandante)
                <div class="col-6 col-md-2">
                    <label for="prioridade" class="form-label small">Prioridade</label>
                    <select id="prioridade" name="prioridade" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($prioridades as $prioridade)
                            <option value="{{ $prioridade->id }}" @selected((string) ($filtros['prioridade'] ?? '') === (string) $prioridade->id)>{{ $prioridade->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="projeto" class="form-label small">Projeto</label>
                    <select id="projeto" name="projeto" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($projetos as $projeto)
                            <option value="{{ $projeto->id }}" @selected((string) ($filtros['projeto'] ?? '') === (string) $projeto->id)>{{ $projeto->titulo }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                <a href="{{ route('demandas.index') }}" class="btn btn-outline-secondary" title="Limpar filtros">Limpar</a>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Demanda</th>
                        @unless ($ehDemandante)
                            <th class="d-none d-md-table-cell">Escola/instituição</th>
                            <th class="d-none d-lg-table-cell">Prioridade</th>
                        @endunless
                        <th class="d-none d-md-table-cell">Aberta em</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitacoes as $item)
                        <tr>
                            <td>
                                <a href="{{ route('demandas.show', $item) }}" class="text-body">{{ $item->titulo }}</a>
                                @if ($item->anexos_count)
                                    <span class="small text-muted" title="Anexos">· {{ $item->anexos_count }} {{ $item->anexos_count === 1 ? 'anexo' : 'anexos' }}</span>
                                @endif
                                <div class="small text-muted">{{ $item->projetoBase->titulo }}</div>
                                @unless ($ehDemandante)
                                    <div class="small text-muted d-md-none">{{ $item->escolaInstituicao->nome }}</div>
                                @endunless
                            </td>
                            @unless ($ehDemandante)
                                <td class="d-none d-md-table-cell small">{{ $item->escolaInstituicao->nome }}</td>
                                <td class="d-none d-lg-table-cell small">
                                    {{ $item->prioridade?->nome ?? '—' }}
                                    @if ($item->complexidade)
                                        <div class="text-muted">Complexidade {{ mb_strtolower($item->complexidade->rotulo()) }}</div>
                                    @endif
                                </td>
                            @endunless
                            <td class="d-none d-md-table-cell small text-nowrap">{{ $item->created_at->format('d/m/Y') }}</td>
                            <td><span class="badge text-bg-{{ $item->status->cor() }}">{{ $item->status->rotulo() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('demandas.show', $item) }}" class="btn btn-sm btn-outline-primary">{{ $ehDemandante ? 'Acompanhar' : 'Abrir' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                @if ($ehDemandante && empty(array_filter($filtros)))
                                    Você ainda não abriu nenhuma demanda.
                                    <div class="mt-2"><a href="{{ route('demandas.create') }}" class="btn btn-primary btn-sm">Abrir a primeira demanda</a></div>
                                @elseif (($filtros['status'] ?? null) === \App\Enums\StatusDemanda::Cadastrada->value && count(array_filter($filtros)) === 1)
                                    Nenhuma demanda nova aguardando análise.
                                @else
                                    Nenhuma demanda encontrada.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $solicitacoes->links() }}
    </div>
</div>
@endsection
