@extends('layouts.app')

@php($solicitacao = $subprojeto->solicitacao)
@php($pendentes = $subprojeto->candidaturas->filter->estaPendente())
@php($avaliadas = $subprojeto->candidaturas->reject->estaPendente())

@section('titulo', $subprojeto->titulo)

@section('content')
<div class="container">
    <div class="mb-2">
        <a href="{{ route('subprojetos.index') }}" class="small">&larr; Sub-projetos</a>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $subprojeto->titulo }}</h1>
            <span class="badge text-bg-{{ $subprojeto->modalidade->cor() }}">{{ $subprojeto->modalidade->rotulo() }}</span>
            @if ($subprojeto->recebeCandidaturas())
                <span class="badge text-bg-success">Recebendo candidaturas</span>
            @else
                <span class="badge text-bg-secondary">Inscrições fechadas</span>
            @endif
            <div class="small text-muted mt-1">
                Demanda: <a href="{{ route('demandas.show', $solicitacao) }}">{{ $solicitacao->titulo }}</a>
                <span class="badge text-bg-{{ $solicitacao->status->cor() }}">{{ $solicitacao->status->rotulo() }}</span>
            </div>
        </div>
        @can('alterarCargaHoraria', $subprojeto)
            <a href="{{ route('subprojetos.edit', $subprojeto) }}" class="btn btn-outline-primary">
                {{ auth()->user()->can('update', $subprojeto) ? 'Editar' : 'Corrigir carga horária' }}
            </a>
        @endcan
    </div>

    @error('candidatura')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    @error('entrega')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
        <div class="col-lg-8">
            {{-- RF11: candidaturas pendentes. --}}
            @if ($gerencia)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Candidaturas pendentes ({{ $pendentes->count() }})</h2>
                        @if ($pendentes->isNotEmpty() && $subprojeto->vagasLivres() === 0)
                            <p class="small text-warning-emphasis">A equipe está completa. Para aprovar mais alguém, aumente o número de vagas.</p>
                        @endif

                        @forelse ($pendentes as $candidatura)
                            @php($bolsa = "candidatura{$candidatura->id}")
                            <div class="border-top py-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                <div>
                                    <div class="fw-semibold">{{ $candidatura->estudante->nome }}</div>
                                    <div class="small text-muted">
                                        {{ $candidatura->estudante->estudante?->curso?->nome }}
                                        · enviada em {{ $candidatura->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-start">
                                    <form method="POST" action="{{ route('subprojetos.candidaturas.aprovar', [$subprojeto, $candidatura]) }}" class="d-flex gap-2" data-confirmar="Aprovar {{ $candidatura->estudante->nome }} e incluir na equipe?">
                                        @csrf
                                        @method('PATCH')
                                        <label for="papel-{{ $candidatura->id }}" class="visually-hidden">Papel na equipe</label>
                                        <select id="papel-{{ $candidatura->id }}" name="papel_id" class="form-select form-select-sm @error('papel_id', $bolsa) is-invalid @enderror">
                                            @foreach ($papeis as $papel)
                                                <option value="{{ $papel->id }}" @selected($papel->id === $papelPadrao?->id)>{{ $papel->nome }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success text-nowrap">Aprovar</button>
                                    </form>
                                    <form method="POST" action="{{ route('subprojetos.candidaturas.recusar', [$subprojeto, $candidatura]) }}" data-confirmar="Recusar a candidatura de {{ $candidatura->estudante->nome }}?">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Recusar</button>
                                    </form>
                                </div>
                            </div>
                            @error('papel_id', $bolsa)
                                <div class="small text-danger">{{ $message }}</div>
                            @enderror
                        @empty
                            <p class="text-muted small mb-0">
                                Nenhuma candidatura aguardando avaliação.
                                @if ($subprojeto->recebeCandidaturas())
                                    O sub-projeto está na vitrine de oportunidades.
                                @endif
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- RF12 e RF13: entregas da equipe e situação da validação. --}}
            @include('subprojetos._entregas')

            {{-- RF11: equipe com papéis. --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 text-muted">Equipe ({{ $subprojeto->membros_ativos_count }} de {{ $subprojeto->vagas }} vagas)</h2>

                    @forelse ($subprojeto->membros->reject->foiRemovido() as $membro)
                        @php($bolsa = "membro{$membro->id}")
                        <div class="border-top py-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>
                                <div class="fw-semibold">{{ $membro->estudante->nome }}</div>
                                <div class="small text-muted">
                                    {{ $membro->papel->nome }} · {{ $membro->estudante->estudante?->curso?->nome }} · desde {{ $membro->created_at->format('d/m/Y') }}
                                </div>
                            </div>
                            @if ($gerencia)
                                <div class="d-flex flex-wrap gap-2 align-items-start">
                                    <form method="POST" action="{{ route('subprojetos.membros.papel', [$subprojeto, $membro]) }}" class="d-flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label for="papel-membro-{{ $membro->id }}" class="visually-hidden">Papel de {{ $membro->estudante->nome }}</label>
                                        <select id="papel-membro-{{ $membro->id }}" name="papel_id" class="form-select form-select-sm @error('papel_id', $bolsa) is-invalid @enderror">
                                            @foreach ($papeis->concat([$membro->papel])->unique('id')->sortBy('nome') as $papel)
                                                <option value="{{ $papel->id }}" @selected($papel->id === $membro->papel_id)>{{ $papel->nome }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Trocar papel</button>
                                    </form>
                                    <form method="POST" action="{{ route('subprojetos.membros.remover', [$subprojeto, $membro]) }}" data-confirmar="Remover {{ $membro->estudante->nome }} da equipe? A participação dele(a) deixa de contar no relatório deste sub-projeto.">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        @error('papel_id', $bolsa)
                            <div class="small text-danger">{{ $message }}</div>
                        @enderror
                    @empty
                        <p class="text-muted small mb-0">Ninguém na equipe ainda.</p>
                    @endforelse

                    @php($removidos = $subprojeto->membros->filter->foiRemovido())
                    @if ($removidos->isNotEmpty() && $gerencia)
                        <details class="mt-2">
                            <summary class="small text-muted">Removidos da equipe ({{ $removidos->count() }})</summary>
                            <ul class="list-unstyled small mt-2 mb-0">
                                @foreach ($removidos as $membro)
                                    <li>{{ $membro->estudante->nome }} — {{ $membro->papel->nome }}, removido(a) em {{ $membro->removido_em->format('d/m/Y') }}</li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </div>

            @if ($gerencia && $avaliadas->isNotEmpty())
                <details class="card shadow-sm mb-3">
                    <summary class="card-body small text-muted">Candidaturas já avaliadas ou canceladas ({{ $avaliadas->count() }})</summary>
                    <div class="card-body pt-0">
                        @foreach ($avaliadas as $candidatura)
                            <div class="d-flex justify-content-between gap-2 border-top py-2 small">
                                <span>{{ $candidatura->estudante->nome }}</span>
                                <span class="text-end">
                                    <span class="badge text-bg-{{ $candidatura->status->cor() }}">{{ $candidatura->status->rotulo() }}</span>
                                    @if ($candidatura->avaliador)
                                        <div class="text-muted">{{ $candidatura->avaliador->nome }}, {{ $candidatura->avaliado_em->format('d/m/Y') }}</div>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Projeto de extensão</dt>
                        <dd>{{ $solicitacao->projetoBase->titulo }}</dd>
                        <dt class="small text-muted">Coordenador responsável</dt>
                        <dd>{{ $subprojeto->coordenador->nome }}</dd>
                        <dt class="small text-muted">Período</dt>
                        <dd>{{ $subprojeto->periodo() }}</dd>
                        <dt class="small text-muted">Carga horária estimada</dt>
                        <dd>{{ $subprojeto->carga_horaria_estimada }} horas</dd>
                        <dt class="small text-muted">Escopo</dt>
                        <dd class="mb-0 small" style="white-space: pre-line">{{ $subprojeto->escopo }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
