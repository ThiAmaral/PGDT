<?php

namespace App\Enums;

/**
 * Tipos de registro na linha do tempo da demanda (RF08). Sub-projeto, carga horária e equipe
 * entram na Fase 5; entrega e validação, na Fase 6.
 */
enum TipoHistorico: string
{
    case Status = 'status';
    case Triagem = 'triagem';
    case Subprojeto = 'subprojeto';
    case CargaHoraria = 'carga_horaria';
    case Equipe = 'equipe';
    case Entrega = 'entrega';
    case Validacao = 'validacao';
    case Outro = 'outro';

    /**
     * Tipos cujo campo `dados` lista campos alterados no formato `{campo: {de, para}}`.
     */
    public function listaAlteracoes(): bool
    {
        return in_array($this, [self::Triagem, self::Subprojeto], true);
    }

    /**
     * Classe de cor do Bootstrap usada no marcador da linha do tempo.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Status => 'primary',
            self::Triagem => 'info',
            self::Subprojeto, self::CargaHoraria => 'warning',
            self::Equipe => 'dark',
            self::Entrega, self::Validacao => 'success',
            default => 'secondary',
        };
    }
}
