<?php

namespace App\Observers;

use App\Models\Mensagem;
use App\Notifications\Aviso;
use Illuminate\Support\Str;

/**
 * RF08 (Fase 7): avisa por e-mail os participantes do chat de cada comentário novo:
 * o demandante, a coordenação e a equipe, menos quem escreveu.
 */
class MensagemObserver
{
    public function created(Mensagem $mensagem): void
    {
        $solicitacao = $mensagem->solicitacao;

        Aviso::enviar([$solicitacao->demandante, ...$solicitacao->coordenacao(), ...$solicitacao->equipe()], new Aviso(
            "Nova mensagem na demanda \"{$solicitacao->titulo}\"",
            [
                "{$mensagem->autor->nome} ({$mensagem->perfil->rotulo()}) escreveu no chat da demanda:",
                Str::limit($mensagem->texto, 500),
            ],
            'Responder no chat',
            route('demandas.show', $solicitacao).'#chat',
        ));
    }
}
