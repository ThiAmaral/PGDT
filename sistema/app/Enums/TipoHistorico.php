<?php

namespace App\Enums;

/**
 * Tipos de registro na linha do tempo da demanda (RF08). Os tipos de sub-projeto, equipe,
 * entrega e validação passam a ser usados nas Fases 5 e 6.
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
     * Classe de cor do Bootstrap usada no marcador da linha do tempo.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Status => 'primary',
            self::Triagem => 'info',
            self::Entrega, self::Validacao => 'success',
            default => 'secondary',
        };
    }
}
