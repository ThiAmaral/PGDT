<?php

namespace App\Policies;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use App\Models\Usuario;

/**
 * RF09 e RF11: quem gerencia a demanda (admin, coordenador do projeto ou responsável pelo sub-projeto)
 * cria o sub-projeto e cuida da equipe. RF10: o estudante ativo se candidata.
 */
class SubprojetoPolicy
{
    public function __construct(private SolicitacaoPolicy $solicitacoes) {}

    /**
     * Lista "Sub-projetos": coordenador e admin veem os que gerenciam; o estudante, as suas equipes.
     */
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador, Perfil::Estudante);
    }

    /**
     * Quem vê a demanda vê o sub-projeto (inclusive o demandante e os membros da equipe).
     */
    public function view(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $this->solicitacoes->view($usuario, $subprojeto->solicitacao);
    }

    /**
     * Uma demanda vira sub-projeto uma única vez, depois de priorizada.
     */
    public function create(Usuario $usuario, Solicitacao $solicitacao): bool
    {
        return $this->solicitacoes->gerencia($usuario, $solicitacao)
            && $solicitacao->status === StatusDemanda::Priorizada
            && ! $solicitacao->subprojeto()->exists();
    }

    /**
     * Escopo, datas, vagas, modalidade e inscrições mudam enquanto a demanda está em andamento.
     */
    public function update(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $this->solicitacoes->gerencia($usuario, $subprojeto->solicitacao)
            && $subprojeto->solicitacao->status->permiteEquipe();
    }

    /**
     * RF15: a carga horária estimada pode ser corrigida em qualquer status, inclusive depois de "Concluída".
     */
    public function alterarCargaHoraria(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $this->solicitacoes->gerencia($usuario, $subprojeto->solicitacao);
    }

    /**
     * RF11: aprovar e recusar candidaturas, trocar papéis e remover membros.
     */
    public function gerenciarEquipe(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $this->update($usuario, $subprojeto);
    }

    /**
     * RF10: só o estudante ativo, sem candidatura anterior, enquanto houver vagas.
     */
    public function candidatar(Usuario $usuario, Subprojeto $subprojeto): bool
    {
        return $subprojeto->impedimentoDeCandidatura($usuario) === null;
    }
}
