<?php

namespace App\Observers;

use App\Enums\StatusCandidatura;
use App\Models\Candidatura;
use App\Notifications\Aviso;

/**
 * RF10 e RF11 (Fase 7): avisa por e-mail a coordenação de cada candidatura nova e o estudante
 * da aprovação ou da recusa. As candidaturas não vão para a linha do tempo (decisão da Fase 5).
 */
class CandidaturaObserver
{
    public function saved(Candidatura $candidatura): void
    {
        if (! $candidatura->wasRecentlyCreated && ! $candidatura->wasChanged('status')) {
            return;
        }

        $subprojeto = $candidatura->subprojeto;

        match ($candidatura->status) {
            StatusCandidatura::Pendente => Aviso::enviar($subprojeto->solicitacao->coordenacao(), new Aviso(
                "Nova candidatura: {$subprojeto->titulo}",
                ["{$candidatura->estudante->nome} se candidatou ao sub-projeto \"{$subprojeto->titulo}\"."],
                'Avaliar a candidatura',
                route('subprojetos.show', $subprojeto),
            )),
            StatusCandidatura::Aprovada => Aviso::enviar([$candidatura->estudante], new Aviso(
                "Candidatura aprovada: {$subprojeto->titulo}",
                ["Sua candidatura ao sub-projeto \"{$subprojeto->titulo}\" foi aprovada e você já faz parte da equipe."],
                'Ver o sub-projeto',
                route('subprojetos.show', $subprojeto),
            )),
            StatusCandidatura::Recusada => Aviso::enviar([$candidatura->estudante], new Aviso(
                "Candidatura recusada: {$subprojeto->titulo}",
                ["Sua candidatura ao sub-projeto \"{$subprojeto->titulo}\" não foi aprovada desta vez.", 'Veja outras vagas abertas na vitrine de oportunidades.'],
                'Ver oportunidades',
                route('oportunidades.index'),
            )),
            StatusCandidatura::Cancelada => null,
        };
    }
}
