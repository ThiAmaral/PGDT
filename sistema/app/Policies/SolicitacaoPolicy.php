<?php

namespace App\Policies;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Models\Solicitacao;
use App\Models\Usuario;

/**
 * RF06 e RF07: o demandante abre e acompanha as suas demandas; o coordenador faz a triagem
 * das demandas dos projetos que coordena; o admin vê e gerencia todas.
 */
class SolicitacaoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador, Perfil::Demandante);
    }

    public function view(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->gerencia($usuario, $solicitacao) || $solicitacao->demandante_id === $usuario->id;
    }

    public function create(Usuario $usuario): bool
    {
        return $usuario->temPerfil(Perfil::Demandante);
    }

    /**
     * Categoria, prioridade e complexidade podem ser revistas até a demanda sair da triagem.
     */
    public function triar(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->gerencia($usuario, $solicitacao) && $solicitacao->status->permiteTriagem();
    }

    public function alterarStatus(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->statusDisponiveis($usuario, $solicitacao) !== [];
    }

    /**
     * Status que o usuário pode aplicar agora (decisão da Fase 0): coordenador e admin conduzem a triagem;
     * o demandante cancela só antes de "Em Desenvolvimento"; o admin cancela a qualquer momento.
     *
     * @return list<StatusDemanda>
     */
    public function statusDisponiveis(Usuario $usuario, Solicitacao $solicitacao): array
    {
        $status = $solicitacao->status;
        $disponiveis = [];

        if ($this->gerencia($usuario, $solicitacao)) {
            $disponiveis = $status->proximosDaTriagem();
        }

        $podeCancelar = match (true) {
            $status === StatusDemanda::Cancelada => false,
            $usuario->temPerfil(Perfil::Admin) => true,
            $usuario->temPerfil(Perfil::Demandante) => $solicitacao->demandante_id === $usuario->id && $status->demandantePodeCancelar(),
            default => false,
        };

        if ($podeCancelar) {
            $disponiveis[] = StatusDemanda::Cancelada;
        }

        return $disponiveis;
    }

    /**
     * Admin, ou o coordenador do Projeto Base da demanda.
     */
    private function gerencia(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $usuario->temPerfil(Perfil::Admin)
            || ($usuario->temPerfil(Perfil::Coordenador) && $solicitacao->projetoBase->coordenador_id === $usuario->id);
    }
}
