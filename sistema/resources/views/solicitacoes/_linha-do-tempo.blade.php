<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h6 text-muted">Linha do tempo</h2>
        <ol class="linha-do-tempo">
            @foreach ($solicitacao->historicos as $historico)
                <li>
                    <span class="ponto bg-{{ $historico->tipo->cor() }}"></span>
                    <div class="small fw-semibold">{{ $historico->descricao }}</div>

                    @if ($historico->tipo === \App\Enums\TipoHistorico::Triagem)
                        <ul class="list-unstyled small mb-0">
                            @foreach ($historico->dados ?? [] as $campo => $valores)
                                <li class="p-0">
                                    {{ ucfirst($campo) }}:
                                    @if ($valores['de'] ?? null)
                                        <span class="text-muted text-decoration-line-through">{{ $valores['de'] }}</span> &rarr;
                                    @endif
                                    {{ $valores['para'] ?? '—' }}
                                </li>
                            @endforeach
                        </ul>
                    @elseif (isset($historico->dados['motivo']))
                        <div class="small">Motivo: {{ $historico->dados['motivo'] }}</div>
                    @endif

                    <div class="small text-muted">
                        {{ $historico->usuario?->nome ?? 'Sistema' }} &middot; {{ $historico->created_at->format('d/m/Y H:i') }}
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</div>
