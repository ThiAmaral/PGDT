{{--
    Card de sub-projeto da vitrine (RF10) e da página pública (RF14). Só dados públicos:
    título, escopo, projeto, coordenador, modalidade, período e vagas.
--}}
@php($impedimento = auth()->check() ? $subprojeto->impedimentoDeCandidatura(auth()->user()) : null)

<div class="card h-100 shadow-sm cartao-oportunidade">
    <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <span class="badge text-bg-{{ $subprojeto->modalidade->cor() }}">{{ $subprojeto->modalidade->rotulo() }}</span>
            <span class="small text-muted text-end">
                {{ $subprojeto->vagasLivres() }} {{ $subprojeto->vagasLivres() === 1 ? 'vaga livre' : 'vagas livres' }} de {{ $subprojeto->vagas }}
            </span>
        </div>

        <h2 class="h5 mb-1">{{ $subprojeto->titulo }}</h2>
        <p class="small text-muted mb-2">{{ $subprojeto->solicitacao->projetoBase->titulo }}</p>

        @if (mb_strlen($subprojeto->escopo) > 220)
            <details class="small mb-2">
                <summary class="text-body">{{ \Illuminate\Support\Str::limit($subprojeto->escopo, 200) }} <span class="link-primary">Ler mais</span></summary>
                <p class="mt-2 mb-0" style="white-space: pre-line">{{ $subprojeto->escopo }}</p>
            </details>
        @else
            <p class="small mb-2" style="white-space: pre-line">{{ $subprojeto->escopo }}</p>
        @endif

        <dl class="small row g-1 mb-3 mt-auto">
            <dt class="col-5 text-muted fw-normal">Coordenação</dt>
            <dd class="col-7 mb-0">{{ $subprojeto->coordenador->nome }}</dd>
            <dt class="col-5 text-muted fw-normal">Período</dt>
            <dd class="col-7 mb-0">{{ $subprojeto->periodo() }}</dd>
            @unless ($publico)
                <dt class="col-5 text-muted fw-normal">Carga horária</dt>
                <dd class="col-7 mb-0">{{ $subprojeto->carga_horaria_estimada }} h (estimada)</dd>
            @endunless
        </dl>

        @if (auth()->guest() || $impedimento === null)
            <a href="{{ route('oportunidades.show', $subprojeto) }}" class="btn btn-primary w-100">
                {{ $publico ? 'Quero participar' : 'Ver e candidatar-se' }}
            </a>
        @else
            <button type="button" class="btn btn-secondary w-100" disabled aria-describedby="impedimento-{{ $subprojeto->id }}">Quero participar</button>
            <p id="impedimento-{{ $subprojeto->id }}" class="small text-muted mt-1 mb-0">{{ $impedimento }}</p>
            @if (auth()->user()->estaAtivo() && $subprojeto->candidaturaDe(auth()->user()))
                <a href="{{ route('oportunidades.show', $subprojeto) }}" class="small">Ver minha candidatura</a>
            @endif
        @endif
    </div>
</div>
