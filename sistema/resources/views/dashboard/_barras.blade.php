{{-- Barras horizontais de uma só cor: o número fica escrito ao lado, e a barra mostra o tamanho relativo. --}}
@php($maximo = max(1, $contagem->max() ?? 0))
@php($soma = max(1, $contagem->sum()))
<div class="card shadow-sm h-100">
    <div class="card-body">
        <h2 class="h6 mb-3">{{ $titulo }}</h2>
        @if ($contagem->sum() === 0)
            <p class="text-muted small mb-0">Nenhuma demanda no período.</p>
        @else
            <dl class="mb-0 barras">
                @foreach ($contagem as $rotulo => $quantidade)
                    @php($percentual = round($quantidade / $soma * 100))
                    <div class="barra-linha" title="{{ $rotulo }}: {{ $quantidade }} {{ $quantidade === 1 ? 'demanda' : 'demandas' }} ({{ $percentual }}%)">
                        <dt class="small fw-normal text-truncate">{{ $rotulo }}</dt>
                        <dd class="d-flex align-items-center gap-2 mb-0">
                            <span class="barra-trilho flex-grow-1">
                                <span class="barra" style="width: {{ $quantidade === 0 ? 0 : max(1, $quantidade / $maximo * 100) }}%"></span>
                            </span>
                            <span class="small text-body text-end barra-valor">{{ $quantidade }} <span class="text-muted">· {{ $percentual }}%</span></span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>
