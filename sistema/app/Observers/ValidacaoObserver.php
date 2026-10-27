<?php

namespace App\Observers;

use App\Enums\DecisaoValidacao;
use App\Enums\EtapaValidacao;
use App\Enums\TipoHistorico;
use App\Models\Validacao;
use App\Notifications\Aviso;

/**
 * RF08 e RF13: registra cada decisão da validação na linha do tempo da demanda, com o feedback.
 * O aceite feito pela coordenação aparece como registrado em nome do demandante. Avisa por e-mail
 * quem precisa agir em seguida (Fase 7).
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

        $this->avisar($validacao);
    }

    /**
     * Aprovada tecnicamente: o demandante é chamado para o aceite e a equipe fica sabendo.
     * Devolvida: a equipe ajusta. Aceita ou com pendência: a equipe e a coordenação ficam sabendo.
     */
    private function avisar(Validacao $validacao): void
    {
        $entrega = $validacao->entrega;
        $solicitacao = $entrega->subprojeto->solicitacao;
        $equipe = $solicitacao->equipe();

        $destinatarios = match ($validacao->decisao) {
            DecisaoValidacao::Aprovada => [$solicitacao->demandante, ...$equipe],
            DecisaoValidacao::Devolvida => $equipe,
            DecisaoValidacao::Aceita, DecisaoValidacao::Pendencia => [...$equipe, ...$solicitacao->coordenacao()],
        };

        $linhas = ["Entrega nº {$entrega->numero} \"{$entrega->titulo}\" da demanda \"{$solicitacao->titulo}\": {$validacao->decisao->rotulo()}."];

        if ($validacao->decisao === DecisaoValidacao::Aprovada) {
            $linhas[] = 'A entrega aguarda o aceite do demandante.';
        }

        if ($validacao->feedback) {
            $linhas[] = "Comentário: {$validacao->feedback}";
        }

        Aviso::enviar($destinatarios, new Aviso(
            "{$validacao->decisao->rotulo()}: {$entrega->titulo}",
            $linhas,
            'Ver a entrega',
            route('subprojetos.entregas.show', [$entrega->subprojeto, $entrega]),
        ));
    }
}
