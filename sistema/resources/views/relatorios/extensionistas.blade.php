@extends('layouts.app')

@section('titulo', $ehEstudante ? 'Meu relatório' : 'Relatório por extensionista')

@section('content')
<div class="container">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $ehEstudante ? 'Meu relatório de demandas executadas' : 'Relatório por extensionista' }}</h1>
            <p class="text-muted small mb-0">
                Sub-projetos de que {{ $ehEstudante ? 'você participa' : 'cada estudante participa' }}, com o período e a carga horária estimada definidos pela coordenação.
            </p>
        </div>
        <a href="{{ route('relatorios.extensionistas.pdf', request()->query()) }}" class="btn btn-outline-primary text-nowrap">Exportar PDF</a>
    </div>

    <form method="GET" action="{{ route('relatorios.extensionistas') }}" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            @unless ($ehEstudante)
                <div class="col-md-6 col-lg-3">
                    <label for="estudante" class="form-label small mb-1">Estudante</label>
                    <select id="estudante" name="estudante" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($estudantes as $estudante)
                            <option value="{{ $estudante->id }}" @selected((string) ($filtros['estudante'] ?? '') === (string) $estudante->id)>{{ $estudante->nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="col-6 col-md-3 col-lg-2">
                <label for="de" class="form-label small mb-1">De</label>
                <input id="de" type="date" name="de" value="{{ $filtros['de'] ?? '' }}" class="form-control form-control-sm @error('de') is-invalid @enderror">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label for="ate" class="form-label small mb-1">Até</label>
                <input id="ate" type="date" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="form-control form-control-sm @error('ate') is-invalid @enderror">
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="projeto" class="form-label small mb-1">Projeto de extensão</label>
                <select id="projeto" name="projeto" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach ($projetos as $projeto)
                        <option value="{{ $projeto->id }}" @selected((string) ($filtros['projeto'] ?? '') === (string) $projeto->id)>{{ $projeto->titulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="status" class="form-label small mb-1">Status da demanda</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="{{ \App\Http\Controllers\RelatorioController::TODOS_OS_STATUS }}" @selected($filtros['status'] === \App\Http\Controllers\RelatorioController::TODOS_OS_STATUS)>Todos</option>
                    @foreach (\App\Enums\StatusDemanda::cases() as $status)
                        <option value="{{ $status->value }}" @selected($filtros['status'] === $status->value)>{{ $status->rotulo() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('relatorios.extensionistas') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
            @if ($errors->any())
                <div class="col-12 small text-danger">{{ $errors->first() }}</div>
            @endif
            <div class="col-12 small text-muted">O período considera os sub-projetos que começaram e terminaram entre as datas escolhidas.</div>
        </div>
    </form>

    @forelse ($porEstudante as $secao)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between gap-1 mb-2">
                    <div>
                        <h2 class="h5 mb-0">{{ $secao['estudante']->nome }}</h2>
                        <div class="small text-muted">
                            {{ $secao['estudante']->estudante?->curso?->nome }}
                            @if ($secao['estudante']->estudante?->matricula)
                                · Matrícula {{ $secao['estudante']->estudante->matricula }}
                            @endif
                        </div>
                    </div>
                    <div class="fw-semibold text-sm-end">Total: {{ $secao['total'] }} h</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sub-projeto</th>
                                <th>Projeto de extensão</th>
                                <th>Escola/instituição</th>
                                <th>Papel</th>
                                <th class="text-nowrap">Início</th>
                                <th class="text-nowrap">Fim</th>
                                <th class="text-end text-nowrap">Carga horária</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($secao['participacoes'] as $membro)
                                @php($subprojeto = $membro->subprojeto)
                                <tr>
                                    <td>
                                        {{ $subprojeto->titulo }}
                                        <span class="badge text-bg-{{ $subprojeto->solicitacao->status->cor() }}">{{ $subprojeto->solicitacao->status->rotulo() }}</span>
                                    </td>
                                    <td class="small">{{ $subprojeto->solicitacao->projetoBase->titulo }}</td>
                                    <td class="small">{{ $subprojeto->solicitacao->escolaInstituicao->nomeCompleto() }}</td>
                                    <td class="small">{{ $membro->papel->nome }}</td>
                                    <td class="small text-nowrap">{{ $subprojeto->data_inicio->format('d/m/Y') }}</td>
                                    <td class="small text-nowrap">{{ $subprojeto->data_fim->format('d/m/Y') }}</td>
                                    <td class="text-end text-nowrap">{{ $subprojeto->carga_horaria_estimada }} h</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Total</th>
                                <th class="text-end text-nowrap">{{ $secao['total'] }} h</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card shadow-sm">
            <div class="card-body text-muted">
                Nenhum sub-projeto encontrado com estes filtros.
                @if ($filtros['status'] === \App\Enums\StatusDemanda::Concluida->value)
                    Por padrão, o relatório mostra só as demandas concluídas; escolha "Todos" no status para ver as que estão em andamento.
                @endif
            </div>
        </div>
    @endforelse
</div>
@endsection
