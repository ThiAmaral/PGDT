<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-start gap-2 mb-2">
            <h2 class="h6 text-muted mb-0">Entregas ({{ $entregas->count() }})</h2>
            @if ($podeEnviarEntrega && $impedimentoDeEntrega === null)
                <a href="{{ route('subprojetos.entregas.create', $subprojeto) }}" class="btn btn-sm btn-primary">
                    {{ $ehMembro ? 'Nova entrega' : 'Registrar entrega da equipe' }}
                </a>
            @endif
        </div>

        @if ($podeEnviarEntrega && $impedimentoDeEntrega !== null)
            <p class="small text-muted">{{ $impedimentoDeEntrega }}</p>
        @endif

        @forelse ($entregas as $entrega)
            <a href="{{ route('subprojetos.entregas.show', [$subprojeto, $entrega]) }}" class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-1 border-top py-2 text-body text-decoration-none">
                <span>
                    <span class="text-muted small">nº {{ $entrega->numero }}</span>
                    <span class="fw-semibold text-break">{{ $entrega->titulo }}</span>
                    <span class="d-block small text-muted">{{ $entrega->tipo->rotulo() }} · {{ $entrega->remetente->nome }} · {{ $entrega->created_at->format('d/m/Y H:i') }}</span>
                </span>
                <span class="badge text-bg-{{ $entrega->situacao->cor() }}">{{ $entrega->situacao->rotulo() }}</span>
            </a>
        @empty
            <p class="text-muted small mb-0">
                @if (auth()->user()->temPerfil(\App\Enums\Perfil::Demandante))
                    Nenhuma entrega aprovada pela coordenação ainda.
                @else
                    Nenhuma entrega enviada ainda.
                @endif
            </p>
        @endforelse
    </div>
</div>
