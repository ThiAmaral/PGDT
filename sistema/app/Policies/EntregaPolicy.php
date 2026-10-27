<?php

namespace App\Policies;

use App\Enums\SituacaoEntrega;
use App\Models\Entrega;
use App\Models\Subprojeto;
use App\Models\Usuario;

/**
 * RF12: os membros da equipe enviam as entregas. RF13: o coordenador faz a validação técnica
 * e o demandante aceita a solução; a coordenação também pode registrar o aceite em nome dele.
 */
class EntregaPolicy
{
    public function __construct(private SolicitacaoPolicy $solicitacoes) {}

    /**
     * Quem vê a demanda vê as entregas; o demandante, só depois da aprovação técnica.
     */
    public function view(Usuario $usuario, Entrega $entrega): bool
    {
        $solicitacao = $entrega->subprojeto->solicitacao;

        if (! $this->solicitacoes->view($usuario, $solicitacao)) {
            return false;
        }

        return $this->solicitacoes->gerencia($usuario, $solicitacao)
            || $solicitacao->demandante_id !== $usuario->id
            || $entrega->situacao->passouPelaEtapaTecnica();
    }

    /**
     * Membros ativos da equipe e, em nome dela, quem gerencia a demanda, com a demanda em execução.
     */
    public function create(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $subprojeto->impedimentoDeEntrega($usuario) === null;
    }

    /**
     * RF13 etapa 1: quem gerencia a demanda aprova tecnicamente ou devolve para ajustes.
     */
    public function validarTecnicamente(Usuario $usuario, Entrega $entrega): bool
    {
        $solicitacao = $entrega->subprojeto->solicitacao;

        return $entrega->situacao === SituacaoEntrega::AguardandoTecnica
            && $solicitacao->status->permiteEntrega()
            && $this->solicitacoes->gerencia($usuario, $solicitacao);
    }

    /**
     * RF13 etapa 2: o demandante que abriu a demanda aceita a solução ou reporta uma pendência.
     * O admin e o coordenador também decidem, quando o demandante confirma por outro meio.
     */
    public function aceitar(Usuario $usuario, Entrega $entrega): bool
    {
        $solicitacao = $entrega->subprojeto->solicitacao;

        return $entrega->situacao === SituacaoEntrega::AguardandoAceite
            && $solicitacao->status->permiteEntrega()
            && ($solicitacao->demandante_id === $usuario->id || $this->solicitacoes->gerencia($usuario, $solicitacao));
    }
}
