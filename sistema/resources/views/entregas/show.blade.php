@extends('layouts.app')

@php($solicitacao = $entrega->subprojeto->solicitacao)
@php($podeValidar = auth()->user()->can('validarTecnicamente', $entrega))
@php($podeAceitar = auth()->user()->can('aceitar', $entrega))
@php($emNomeDoDemandante = $podeAceitar && $solicitacao->demandante_id !== auth()->id())

@section('titulo', "{$entrega->rotulo()}: {$entrega->titulo}")

@section('content')
<div class="container">
    <div class="mb-2">
        @if (auth()->user()->temPerfil(\App\Enums\Perfil::Demandante))
            <a href="{{ route('demandas.show', $solicitacao) }}" class="small">&larr; {{ $solicitacao->titulo }}</a>
        @else
            <a href="{{ route('subprojetos.show', $subprojeto) }}" class="small">&larr; {{ $subprojeto->titulo }}</a>
        @endif
    </div>

    <div class="mb-3">
        <div class="small text-muted">{{ $entrega->rotulo() }}</div>
        <h1 class="h3 mb-1">{{ $entrega->titulo }}</h1>
        <span class="badge text-bg-{{ $entrega->situacao->cor() }}">{{ $entrega->situacao->rotulo() }}</span>
        <span class="small text-muted ms-1">{{ $entrega->tipo->rotulo() }} · enviada por {{ $entrega->remetente->nome }}@if ($entrega->enviadaPelaCoordenacao()) (coordenação, em nome da equipe)@endif em {{ $entrega->created_at->format('d/m/Y \à\s H:i') }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    @if ($entrega->url)
                        <h2 class="h6 text-muted">Link ou repositório</h2>
                        <p class="text-break"><a href="{{ $entrega->url }}" target="_blank" rel="noopener noreferrer">{{ $entrega->url }}</a></p>
                    @endif

                    <h2 class="h6 text-muted">Descrição</h2>
                    @if ($entrega->descricao)
                        <p style="white-space: pre-line" class="mb-0">{{ $entrega->descricao }}</p>
                    @else
                        <p class="text-muted mb-0">Sem descrição.</p>
                    @endif
                </div>
            </div>

            @if ($entrega->arquivos->isNotEmpty())
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Arquivos</h2>
                        @foreach ($entrega->arquivos as $arquivo)
                            <div class="d-flex justify-content-between align-items-center border-top py-2">
                                <div class="text-break me-2">
                                    <a href="{{ route('subprojetos.entregas.arquivo', [$subprojeto, $entrega, $arquivo]) }}">{{ $arquivo->nome_original }}</a>
                                    <div class="small text-muted">{{ $arquivo->tamanhoLegivel() }}</div>
                                </div>
                                <a href="{{ route('subprojetos.entregas.arquivo', [$subprojeto, $entrega, $arquivo]) }}" class="btn btn-sm btn-outline-secondary">Baixar</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- RF13: etapa 1 (coordenação) ou etapa 2 (demandante). --}}
            @if ($podeValidar || $podeAceitar)
                @php($decisoes = $podeValidar ? $decisoesTecnicas : $decisoesAceite)
                <div class="card shadow-sm mb-3 border-primary">
                    <div class="card-body">
                        <h2 class="h6 text-muted">{{ $podeValidar ? 'Validação técnica' : 'Aceite da solução' }}</h2>
                        <p class="small">
                            @if ($podeValidar)
                                Confira se a entrega atende ao escopo do sub-projeto. Aprovada, ela segue para o demandante aceitar; devolvida, volta para a equipe com o seu comentário.
                            @elseif ($emNomeDoDemandante)
                                Aguardando o aceite de {{ $solicitacao->demandante->nome }}. Se o demandante já confirmou por outro meio (reunião, telefone, e-mail), registre a decisão em nome dele. A linha do tempo mostra que foi a coordenação que registrou.
                            @else
                                A coordenação aprovou esta entrega. Confira se a solução atende à sua demanda: aceite para concluir ou reporte o que ainda falta.
                            @endif
                        </p>

                        <form method="POST" action="{{ $podeValidar ? route('subprojetos.entregas.validar', [$subprojeto, $entrega]) : route('subprojetos.entregas.aceitar', [$subprojeto, $entrega]) }}" data-decisao-validacao novalidate>
                            @csrf

                            <div class="mb-2">
                                <label for="feedback" class="form-label small mb-1">Comentário</label>
                                <textarea id="feedback" name="feedback" rows="3" maxlength="2000" class="form-control form-control-sm @error('feedback') is-invalid @enderror">{{ old('feedback') }}</textarea>
                                <div class="form-text">Obrigatório para {{ $podeValidar ? 'devolver para ajustes' : 'reportar uma pendência' }}. A equipe verá o comentário.</div>
                                @error('feedback')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('decisao')
                                    <div class="small text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                @foreach ($decisoes as $decisao)
                                    <button type="submit" name="decisao" value="{{ $decisao->value }}"
                                            class="btn {{ $decisao->exigeFeedback() ? 'btn-outline-danger' : 'btn-success' }}"
                                            data-exige-feedback="{{ $decisao->exigeFeedback() ? 1 : 0 }}"
                                            data-confirmar="{{ $emNomeDoDemandante ? 'Registrar em nome do demandante: '.mb_strtolower($decisao->rotulo()).'?' : $decisao->confirmacao() }}">
                                        {{ $decisao->acao() }}
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>
            @elseif ($entrega->situacao === \App\Enums\SituacaoEntrega::AguardandoTecnica && auth()->user()->can('gerencia', $solicitacao))
                <div class="alert alert-secondary small">A demanda não está em execução: mude o status para "Em Desenvolvimento" ou "Em Validação" para validar esta entrega.</div>
            @endif
        </div>

        <div class="col-lg-4">
            {{-- RF13: decisões das duas etapas, da mais antiga para a mais recente. --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted">Validação</h2>
                    <ol class="linha-do-tempo">
                        <li>
                            <span class="ponto bg-success"></span>
                            <div class="small fw-semibold">Entrega enviada</div>
                            <div class="small text-muted">{{ $entrega->remetente->nome }} &middot; {{ $entrega->created_at->format('d/m/Y H:i') }}</div>
                        </li>
                        @foreach ($entrega->validacoes as $validacao)
                            <li>
                                <span class="ponto bg-{{ $validacao->decisao->exigeFeedback() ? 'danger' : 'success' }}"></span>
                                <div class="small fw-semibold">{{ $validacao->etapa->rotulo() }}: {{ $validacao->decisao->rotulo() }}</div>
                                @if ($validacao->feedback)
                                    <div class="small" style="white-space: pre-line">{{ $validacao->feedback }}</div>
                                @endif
                                <div class="small text-muted">{{ $validacao->usuario->nome }} &middot; {{ $validacao->created_at->format('d/m/Y H:i') }}</div>
                            </li>
                        @endforeach
                        @if ($entrega->situacao->emAvaliacao())
                            <li>
                                <span class="ponto bg-secondary"></span>
                                <div class="small text-muted">{{ $entrega->situacao->rotulo() }}</div>
                            </li>
                        @endif
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
