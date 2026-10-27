<?php

namespace App\Observers;

use App\Enums\EtapaValidacao;
use App\Enums\TipoHistorico;
use App\Models\Validacao;

/**
 * RF08 e RF13: registra cada decisão da validação na linha do tempo da demanda, com o feedback.
 * O aceite feito pela coordenação aparece como registrado em nome do demandante.
 */
class ValidacaoObserver
{
    public function created(Validacao $validacao): void
    {
        $entrega = $validacao->entrega;
        $solicitacao = $entrega->subprojeto->solicitacao;
        $emNomeDoDemandante = $validacao->etapa === EtapaValidacao::Aceite && $validacao->usuario_id !== $solicitacao->demandante_id;

        $solicitacao->historicos()->create([
            'usuario_id' => $validacao->usuario_id,
            'tipo' => TipoHistorico::Validacao,
            'descricao' => "{$validacao->etapa->rotulo()} da entrega nº {$entrega->numero} \"{$entrega->titulo}\": {$validacao->decisao->rotulo()}"
                .($emNomeDoDemandante ? ' (registrado pela coordenação em nome do demandante)' : ''),
            'dados' => array_filter([
                'entrega_id' => $entrega->id,
                'numero' => $entrega->numero,
                'etapa' => $validacao->etapa->value,
                'decisao' => $validacao->decisao->value,
                'feedback' => $validacao->feedback,
                'em_nome_do_demandante' => $emNomeDoDemandante ?: null,
            ], fn ($valor) => $valor !== null),
        ]);
    }
}
