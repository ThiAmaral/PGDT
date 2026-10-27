<?php

namespace App\Policies;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Models\Solicitacao;
use App\Models\Usuario;

/**
 * RF06, RF07 e RF08: o demandante abre e acompanha as suas demandas; o coordenador faz a triagem
 * das demandas dos projetos que coordena; o admin vê e gerencia todas. Os três conversam no chat,
 * com os estudantes da equipe do sub-projeto (decisão da Fase 0).
 */
class SolicitacaoPolicy
{
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador, Perfil::Demandante);
    }

    public function view(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->gerencia($usuario, $solicitacao)
            || $solicitacao->demandante_id === $usuario->id
            || $this->ehMembroDaEquipe($usuario, $solicitacao);
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

    /**
     * RF08: quem vê a demanda conversa no chat enquanto ela não estiver concluída ou cancelada.
     */
    public function comentar(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->view($usuario, $solicitacao) && ! $solicitacao->status->encerrada();
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

            // RF09: com o sub-projeto criado, a demanda pode ir para "Em Desenvolvimento" (sugerido primeiro).
            if ($status === StatusDemanda::Priorizada && $solicitacao->subprojeto()->exists()) {
                array_unshift($disponiveis, StatusDemanda::EmDesenvolvimento);
            }

            $disponiveis = [...$disponiveis, ...$this->proximosDaExecucao($solicitacao)];
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
     * RF13: "Em Validação" só com uma entrega enviada e "Concluída" só com a solução aceita.
     * O status sugerido pela última entrega vem primeiro na lista.
     *
     * @return list<StatusDemanda>
     */
    private function proximosDaExecucao(Solicitacao $solicitacao): array
    {
        $subprojeto = $solicitacao->subprojeto;

        if ($subprojeto === null) {
            return [];
        }

        $temEntrega = $subprojeto->entregas()->exists();
        $aceita = $subprojeto->temEntregaAceita();

        $proximos = array_values(array_filter(
            $solicitacao->status->proximosDaExecucao(),
            fn (StatusDemanda $proximo) => match ($proximo) {
                StatusDemanda::EmValidacao => $temEntrega,
                StatusDemanda::Concluida => $aceita,
                default => true,
            },
        ));

        $sugerido = $solicitacao->statusSugerido();

        usort($proximos, fn (StatusDemanda $a, StatusDemanda $b) => ($b === $sugerido) <=> ($a === $sugerido));

        return $proximos;
    }

    /**
     * Admin, o coordenador do Projeto Base da demanda ou o coordenador responsável pelo sub-projeto.
     */
    public function gerencia(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        if ($usuario->temPerfil(Perfil::Admin)) {
            return true;
        }

        return $usuario->temPerfil(Perfil::Coordenador)
            && ($solicitacao->projetoBase->coordenador_id === $usuario->id
                || $solicitacao->subprojeto?->coordenador_id === $usuario->id);
    }

    /**
     * Estudante que está na equipe do sub-projeto (membros removidos perdem o acesso).
     */
    private function ehMembroDaEquipe(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $usuario->temPerfil(Perfil::Estudante)
            && $solicitacao->subprojeto?->ehMembroAtivo($usuario) === true;
    }
}
