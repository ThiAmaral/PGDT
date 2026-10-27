<?php

namespace App\Observers;

use App\Enums\TipoHistorico;
use App\Models\Entrega;
use App\Notifications\Aviso;
use Illuminate\Support\Facades\Auth;

/**
 * RF08 e RF12: registra cada entrega na linha do tempo da demanda, indicando quando a coordenação
 * enviou em nome da equipe. Avisa a coordenação por e-mail que há entrega para validar (Fase 7).
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

        $solicitacao = $entrega->subprojeto->solicitacao;

        Aviso::enviar($solicitacao->coordenacao(), new Aviso(
            "Nova entrega para validar: {$solicitacao->titulo}",
            [
                "A equipe do sub-projeto \"{$entrega->subprojeto->titulo}\" enviou a entrega nº {$entrega->numero}: \"{$entrega->titulo}\".",
                'Ela aguarda a validação técnica.',
            ],
            'Avaliar a entrega',
            route('subprojetos.entregas.show', [$entrega->subprojeto, $entrega]),
        ));
    }
}
