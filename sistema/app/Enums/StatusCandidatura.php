<?php

namespace App\Enums;

/**
 * Situação da candidatura do estudante a um sub-projeto (RF10 e RF11).
 */
enum StatusCandidatura: string
{
    case Pendente = 'pendente';
    case Aprovada = 'aprovada';
    case Recusada = 'recusada';
    case Cancelada = 'cancelada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando avaliação',
            self::Aprovada => 'Aprovada',
            self::Recusada => 'Recusada',
            self::Cancelada => 'Cancelada',
        };
    }

    /**
     * Classe de cor do Bootstrap usada no badge da candidatura.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Aprovada => 'success',
            self::Recusada => 'danger',
            self::Cancelada => 'secondary',
        };
    }
}
