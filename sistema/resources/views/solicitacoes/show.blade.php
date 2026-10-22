@extends('layouts.app')

@php($ehDemandante = auth()->user()->temPerfil(\App\Enums\Perfil::Demandante))

@section('titulo', $solicitacao->titulo)

@section('content')
<div class="container">
    <div class="mb-2">
        @if (auth()->user()->temPerfil(\App\Enums\Perfil::Estudante))
            <a href="{{ route('subprojetos.index') }}" class="small">&larr; Meus sub-projetos</a>
        @else
            <a href="{{ route('demandas.index') }}" class="small">&larr; {{ $ehDemandante ? 'Minhas demandas' : 'Demandas' }}</a>
        @endif
    </div>

    <div class="mb-3">
        <h1 class="h3 mb-1">{{ $solicitacao->titulo }}</h1>
        <span class="badge text-bg-{{ $solicitacao->status->cor() }}">{{ $solicitacao->status->rotulo() }}</span>
        <span class="small text-muted ms-1">Aberta em {{ $solicitacao->created_at->format('d/m/Y \à\s H:i') }}</span>
    </div>

    {{-- RF08: barra de progresso. --}}
    @include('solicitacoes._progresso')

    @if ($solicitacao->motivo_status)
        <div class="alert alert-{{ $solicitacao->status === \App\Enums\StatusDemanda::Recusada ? 'danger' : 'secondary' }}">
            <strong>Motivo ({{ mb_strtolower($solicitacao->status->rotulo()) }}):</strong> {{ $solicitacao->motivo_status }}
        </div>
    @endif

    @error('status')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 text-muted">Descrição</h2>
                    <p style="white-space: pre-line">{{ $solicitacao->descricao }}</p>

                    <h2 class="h6 text-muted">Justificativa</h2>
                    <p style="white-space: pre-line">{{ $solicitacao->justificativa }}</p>

                    <h2 class="h6 text-muted">Público afetado</h2>
                    <p class="mb-0">{{ $solicitacao->publico_afetado }}</p>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 text-muted">Anexos</h2>
                    @forelse ($solicitacao->anexos as $anexo)
                        <div class="d-flex justify-content-between align-items-center border-top py-2">
                            <div class="text-break me-2">
                                <a href="{{ route('demandas.anexo', [$solicitacao, $anexo]) }}">{{ $anexo->nome_original }}</a>
                                <div class="small text-muted">{{ $anexo->tamanhoLegivel() }}</div>
                            </div>
                            <a href="{{ route('demandas.anexo', [$solicitacao, $anexo]) }}" class="btn btn-sm btn-outline-secondary">Baixar</a>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Nenhum arquivo enviado.</p>
                    @endforelse
                </div>
            </div>

            {{-- RF08: chat da demanda. --}}
            @include('solicitacoes._chat')
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-muted">Projeto de extensão</dt>
                        <dd>
                            {{ $solicitacao->projetoBase->titulo }}
                            <div class="small text-muted">Coordenação: {{ $solicitacao->projetoBase->coordenador?->nome ?? 'a definir' }}</div>
                        </dd>

                        <dt class="small text-muted">Escola/instituição</dt>
                        <dd>{{ $solicitacao->escolaInstituicao->nomeCompleto() }}</dd>

                        <dt class="small text-muted">Demandante</dt>
                        <dd class="mb-0">
                            {{ $solicitacao->demandante->nome }}
                            {{-- Contatos do demandante só para a coordenação e o admin. --}}
                            @can('gerencia', $solicitacao)
                                @if ($solicitacao->demandante->demandante?->cargo)
                                    <div class="small text-muted">{{ $solicitacao->demandante->demandante->cargo }}</div>
                                @endif
                                <div class="small"><a href="mailto:{{ $solicitacao->demandante->email }}">{{ $solicitacao->demandante->email }}</a></div>
                                @if ($solicitacao->demandante->telefone)
                                    <div class="small">{{ $solicitacao->demandante->telefone }}</div>
                                @endif
                            @endcan
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- RF09: sub-projeto criado a partir da demanda. --}}
            @if ($solicitacao->subprojeto)
                @php($subprojeto = $solicitacao->subprojeto)
                <div class="card shadow-sm mb-3 border-primary-subtle">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Sub-projeto</h2>
                        <p class="fw-semibold mb-1">{{ $subprojeto->titulo }}</p>
                        <dl class="small row g-1 mb-2">
                            <dt class="col-5 text-muted fw-normal">Modalidade</dt>
                            <dd class="col-7 mb-0">{{ $subprojeto->modalidade->rotulo() }}</dd>
                            <dt class="col-5 text-muted fw-normal">Período</dt>
                            <dd class="col-7 mb-0">{{ $subprojeto->periodo() }}</dd>
                            <dt class="col-5 text-muted fw-normal">Carga horária</dt>
                            <dd class="col-7 mb-0">{{ $subprojeto->carga_horaria_estimada }} h (estimada)</dd>
                            <dt class="col-5 text-muted fw-normal">Equipe</dt>
                            <dd class="col-7 mb-0">{{ $subprojeto->membros_ativos_count }} de {{ $subprojeto->vagas }} vagas</dd>
                        </dl>
                        <a href="{{ route('subprojetos.show', $subprojeto) }}" class="btn btn-sm btn-outline-primary w-100">
                            {{ auth()->user()->can('gerenciarEquipe', $subprojeto) ? 'Equipe e candidaturas' : 'Ver equipe' }}
                        </a>
                    </div>
                </div>
            @elseif (auth()->user()->can('create', [\App\Models\Subprojeto::class, $solicitacao]))
                <div class="card shadow-sm mb-3 border-primary">
                    <div class="card-body">
                        <h2 class="h6 text-muted">Sub-projeto</h2>
                        <p class="small">Demanda priorizada. Crie o sub-projeto para definir escopo, período, carga horária e vagas e abrir as candidaturas dos estudantes.</p>
                        <a href="{{ route('subprojetos.create', $solicitacao) }}" class="btn btn-primary btn-sm w-100">Criar sub-projeto</a>
                    </div>
                </div>
            @endif

            {{-- RF07: triagem. O demandante vê a classificação depois que ela é feita. --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h2 class="h6 text-muted">Triagem</h2>
                    @can('triar', $solicitacao)
                        <form method="POST" action="{{ route('demandas.triagem', $solicitacao) }}" novalidate>
                            @csrf
                            @method('PATCH')

                            <div class="mb-2">
                                <label for="categoria_id" class="form-label small mb-1">Categoria <span class="text-danger">*</span></label>
                                <select id="categoria_id" name="categoria_id" class="form-select form-select-sm @error('categoria_id') is-invalid @enderror" required>
                                    <option value="">Escolha...</option>
                                    @foreach ($categorias as $categoria)
                                        <option value="{{ $categoria->id }}" @selected((string) old('categoria_id', $solicitacao->categoria_id) === (string) $categoria->id)>{{ $categoria->nome }}</option>
                                    @endforeach
                                </select>
                                @error('categoria_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-2">
                                <label for="prioridade_id" class="form-label small mb-1">Prioridade <span class="text-danger">*</span></label>
                                <select id="prioridade_id" name="prioridade_id" class="form-select form-select-sm @error('prioridade_id') is-invalid @enderror" required>
                                    <option value="">Escolha...</option>
                                    @foreach ($prioridades as $prioridade)
                                        <option value="{{ $prioridade->id }}" @selected((string) old('prioridade_id', $solicitacao->prioridade_id) === (string) $prioridade->id)>{{ $prioridade->nome }}</option>
                                    @endforeach
                                </select>
                                @error('prioridade_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <span class="form-label small mb-1 d-block">Complexidade <span class="text-danger">*</span></span>
                                <div class="btn-group w-100" role="group" aria-label="Complexidade">
                                    @foreach ($complexidades as $complexidade)
                                        <input type="radio" class="btn-check" name="complexidade" id="complexidade-{{ $complexidade->value }}" value="{{ $complexidade->value }}" autocomplete="off" @checked(old('complexidade', $solicitacao->complexidade?->value) === $complexidade->value)>
                                        <label class="btn btn-sm btn-outline-secondary" for="complexidade-{{ $complexidade->value }}">{{ $complexidade->rotulo() }}</label>
                                    @endforeach
                                </div>
                                @error('complexidade')
                                    <div class="small text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100">Salvar classificação</button>
                        </form>
                    @elseif ($solicitacao->foiTriada())
                        <dl class="mb-0 small">
                            <dt class="text-muted fw-normal">Categoria</dt>
                            <dd>{{ $solicitacao->categoria->nome }}</dd>
                            <dt class="text-muted fw-normal">Prioridade</dt>
                            <dd>{{ $solicitacao->prioridade->nome }}</dd>
                            <dt class="text-muted fw-normal">Complexidade</dt>
                            <dd class="mb-0">{{ $solicitacao->complexidade->rotulo() }}</dd>
                        </dl>
                    @else
                        <p class="small text-muted mb-0">Ainda não classificada pela coordenação.</p>
                    @endcan

                    @if ($solicitacao->triado_em && ! $ehDemandante)
                        <p class="small text-muted mt-2 mb-0">Classificada por {{ $solicitacao->triador?->nome }} em {{ $solicitacao->triado_em->format('d/m/Y H:i') }}.</p>
                    @endif
                </div>
            </div>

            {{-- Mudança manual de status (decisão da Fase 0). --}}
            @if ($statusDisponiveis)
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        @if ($ehDemandante)
                            <details @if ($errors->has('motivo_status')) open @endif>
                                <summary class="small text-danger">Não precisa mais desta demanda? Cancelar</summary>
                                <div class="mt-2">
                        @else
                            <h2 class="h6 text-muted">Status</h2>
                        @endif

                        @unless ($ehDemandante)
                            @if ($solicitacao->status === \App\Enums\StatusDemanda::Cadastrada)
                                <p class="small text-muted">Ao começar a análise, mude o status para "Em Análise". O demandante acompanha cada mudança.</p>
                            @elseif ($solicitacao->status === \App\Enums\StatusDemanda::EmAnalise && $solicitacao->foiTriada())
                                <p class="small text-muted">Classificação feita. Se a análise terminou, mude para "Priorizada".</p>
                            @elseif ($solicitacao->status === \App\Enums\StatusDemanda::EmAnalise)
                                <p class="small text-muted">Para priorizar, salve antes a categoria, a prioridade e a complexidade.</p>
                            @elseif ($solicitacao->status === \App\Enums\StatusDemanda::Priorizada && $solicitacao->subprojeto)
                                <p class="small text-muted">Sub-projeto criado. Se a equipe já pode começar, mude para "Em Desenvolvimento".</p>
                            @elseif ($solicitacao->status === \App\Enums\StatusDemanda::Priorizada)
                                <p class="small text-muted">Para mudar para "Em Desenvolvimento", crie antes o sub-projeto.</p>
                            @endif
                        @endunless

                        <form method="POST" action="{{ route('demandas.status', $solicitacao) }}" data-confirmar-status data-status-atual="{{ $solicitacao->status->rotulo() }}" novalidate>
                            @csrf
                            @method('PATCH')

                            <div class="mb-2 @if ($ehDemandante) d-none @endif">
                                <label for="status-novo" class="form-label small mb-1">Novo status</label>
                                <select id="status-novo" name="status" class="form-select form-select-sm" data-campo-motivo="#bloco-motivo">
                                    @foreach ($statusDisponiveis as $status)
                                        <option value="{{ $status->value }}" data-exige-motivo="{{ $status->exigeMotivo() ? 1 : 0 }}" @selected(old('status') === $status->value)>{{ $status->rotulo() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="bloco-motivo" class="mb-2">
                                <label for="motivo_status" class="form-label small mb-1">Motivo <span class="text-danger">*</span></label>
                                <textarea id="motivo_status" name="motivo_status" rows="2" maxlength="1000" class="form-control form-control-sm @error('motivo_status') is-invalid @enderror">{{ old('motivo_status') }}</textarea>
                                <div class="form-text">{{ $ehDemandante ? 'A coordenação do projeto verá o motivo.' : 'O demandante verá o motivo.' }}</div>
                                @error('motivo_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-sm w-100 {{ $ehDemandante ? 'btn-outline-danger' : 'btn-outline-primary' }}">
                                {{ $ehDemandante ? 'Cancelar demanda' : 'Mudar status' }}
                            </button>
                        </form>
                        @if ($ehDemandante)
                                </div>
                            </details>
                        @endif
                    </div>
                </div>
            @endif

            {{-- RF08: linha do tempo. --}}
            @include('solicitacoes._linha-do-tempo')
        </div>
    </div>
</div>
@endsection
