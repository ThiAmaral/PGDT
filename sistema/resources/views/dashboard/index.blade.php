@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('content')
<div class="container">
    <h1 class="h3 mb-1">Dashboard gerencial</h1>
    <p class="text-muted small mb-3">
        Demandas abertas no período{{ auth()->user()->temPerfil(\App\Enums\Perfil::Coordenador) ? ' nos projetos que você coordena' : '' }}. Os tempos vêm da linha do tempo de cada demanda.
    </p>

    <form method="GET" action="{{ route('dashboard') }}" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-6 col-md-3 col-lg-2">
                <label for="de" class="form-label small mb-1">Abertas de</label>
                <input id="de" type="date" name="de" value="{{ $filtros['de'] ?? '' }}" class="form-control form-control-sm @error('de') is-invalid @enderror">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label for="ate" class="form-label small mb-1">Até</label>
                <input id="ate" type="date" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="form-control form-control-sm @error('ate') is-invalid @enderror">
            </div>
            <div class="col-md-6 col-lg-4">
                <label for="projeto" class="form-label small mb-1">Projeto de extensão</label>
                <select id="projeto" name="projeto" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach ($projetos as $projeto)
                        <option value="{{ $projeto->id }}" @selected((string) ($filtros['projeto'] ?? '') === (string) $projeto->id)>{{ $projeto->titulo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
            </div>
            @if ($errors->any())
                <div class="col-12 small text-danger">{{ $errors->first() }}</div>
            @endif
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Demandas', $total, 'abertas no período'],
            ['Em triagem', $emTriagem, 'Cadastrada, Em Análise ou Priorizada'],
            ['Em execução', $emExecucao, 'Em Desenvolvimento, Em Validação ou Aguardando Ajustes'],
            ['Concluídas', $concluidas, $total ? round($concluidas / $total * 100).'% das demandas' : 'nenhuma demanda'],
        ] as [$rotulo, $valor, $detalhe])
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $rotulo }}</div>
                        <div class="display-6">{{ $valor }}</div>
                        <div class="small text-muted">{{ $detalhe }}</div>
                    </div>
                </div>
            </div>
        @endforeach
        @foreach ([
            ['Tempo médio de atendimento', $tempoDeAtendimento, 'da abertura até "Concluída"', 'concluída'],
            ['Tempo médio da primeira resposta', $tempoDePrimeiraResposta, 'da abertura até sair de "Cadastrada"', 'analisada'],
        ] as [$rotulo, $tempo, $detalhe, $palavra])
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-muted">{{ $rotulo }}</div>
                        @if ($tempo['dias'] === null)
                            <div class="h3 mb-0">—</div>
                            <div class="small text-muted">Nenhuma demanda {{ $palavra }} no período.</div>
                        @else
                            <div class="display-6">{{ number_format($tempo['dias'], 1, ',', '.') }} <span class="fs-5">{{ $tempo['dias'] == 1 ? 'dia' : 'dias' }}</span></div>
                            <div class="small text-muted">{{ $detalhe }} · média de {{ $tempo['demandas'] }} {{ $tempo['demandas'] === 1 ? 'demanda' : 'demandas' }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            @include('dashboard._barras', ['titulo' => 'Por status', 'contagem' => $porStatus])
        </div>
        <div class="col-lg-6">
            @include('dashboard._barras', ['titulo' => 'Por categoria', 'contagem' => $porCategoria])
        </div>
        <div class="col-lg-6">
            @include('dashboard._barras', ['titulo' => 'Por escola ou instituição', 'contagem' => $porEscola])
        </div>
        <div class="col-lg-6">
            @include('dashboard._barras', ['titulo' => 'Por projeto de extensão', 'contagem' => $porProjeto])
        </div>
    </div>
</div>
@endsection
