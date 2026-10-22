<?php

namespace App\Policies;

use App\Enums\Perfil;
use App\Models\ProjetoBase;
use App\Models\Usuario;

/**
 * RF05: só o Coordenador projeto de extensão e o Admin cadastram e editam Projetos Base.
 */
class ProjetoBasePolicy
{
    /**
     * Todos os perfis ativos consultam a lista (o demandante escolhe o projeto ao abrir a demanda).
     */
    public function viewAny(Usuario $usuario): bool
    {
        return true;
    }

    /**
     * Demandante e estudante só veem projetos ativos.
     */
    public function view(Usuario $usuario, ProjetoBase $projetoBase): bool
    {
        return $projetoBase->ativo || $this->gerencia($usuario);
    }

    public function create(Usuario $usuario): bool
    {
        return $this->gerencia($usuario);
    }

    /**
     * O coordenador edita só os projetos que coordena; o admin edita todos.
     */
    public function update(Usuario $usuario, ProjetoBase $projetoBase): bool
    {
        return $usuario->temPerfil(Perfil::Admin)
            || ($usuario->temPerfil(Perfil::Coordenador) && $projetoBase->coordenador_id === $usuario->id);
    }

    /**
     * Só o admin troca o coordenador responsável.
     */
    public function trocarCoordenador(Usuario $usuario, ProjetoBase $projetoBase): bool
    {
        return $usuario->temPerfil(Perfil::Admin);
    }

    /**
     * Admin e coordenadores veem também os projetos inativos.
     */
    public function gerencia(Usuario $usuario): bool
    {
        return $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador);
    }
}
