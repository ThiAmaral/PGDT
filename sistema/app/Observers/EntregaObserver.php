<?php

namespace App\Observers;

use App\Enums\TipoHistorico;
use App\Models\Entrega;
use Illuminate\Support\Facades\Auth;

/**
 * RF08 e RF12: registra cada entrega na linha do tempo da demanda, indicando quando a coordenação
 * enviou em nome da equipe.
 */
class EntregaObserver
{
    public function created(Entrega $entrega): void
    {
        $emNomeDaEquipe = $entrega->enviadaPelaCoordenacao();

        $entrega->subprojeto->solicitacao->historicos()->create([
            'usuario_id' => Auth::id() ?? $entrega->enviado_por,
            'tipo' => TipoHistorico::Entrega,
            'descricao' => "{$entrega->rotulo()} enviada: {$entrega->titulo}"
                .($emNomeDaEquipe ? ' (enviada pela coordenação em nome da equipe)' : ''),
            'dados' => array_filter([
                'entrega_id' => $entrega->id,
                'numero' => $entrega->numero,
                'tipo' => $entrega->tipo->value,
                'em_nome_da_equipe' => $emNomeDaEquipe ?: null,
            ], fn ($valor) => $valor !== null),
        ]);
    }
}
