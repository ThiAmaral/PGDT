@php
    $etapaAtual = $solicitacao->etapaDoProgresso();
    $concluida = $solicitacao->status === \App\Enums\StatusDemanda::Concluida;
    // Recusada ou cancelada: a barra para na etapa em que a demanda estava.
    $parada = $solicitacao->status->etapa() === null;
    $etapas = \App\Enums\StatusDemanda::etapas();
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <ol class="progresso-demanda" aria-label="Andamento da demanda">
            @foreach ($etapas as $indice => $etapa)
                @php
                    $feita = $indice < $etapaAtual || ($concluida && $indice === $etapaAtual);
                    $atual = $indice === $etapaAtual && ! $concluida;
                @endphp
                <li class="@if ($feita) feita @elseif ($atual) atual @if ($parada) parada @endif @endif" @if ($indice === $etapaAtual) aria-current="step" @endif>
                    <span class="marcador" aria-hidden="true">
                        @if ($feita)
                            &#10003;
                        @elseif ($atual && $parada)
                            &#10005;
                        @else
                            {{ $indice + 1 }}
                        @endif
                    </span>
                    <span class="rotulo">{{ $atual ? $solicitacao->status->rotulo() : $etapa->rotulo() }}</span>
                </li>
            @endforeach
        </ol>

        @if ($parada)
            <p class="small text-muted text-center mt-2 mb-0">
                Demanda {{ mb_strtolower($solicitacao->status->rotulo()) }} na etapa "{{ $etapas[$etapaAtual]->rotulo() }}".
            </p>
        @endif
    </div>
</div>
