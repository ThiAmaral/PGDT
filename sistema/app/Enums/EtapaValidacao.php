<?php

namespace App\Enums;

/**
 * As duas etapas da validação da entrega (RF13).
 */
enum EtapaValidacao: string
{
    case Tecnica = 'tecnica';
    case Aceite = 'aceite';

    public function rotulo(): string
    {
        return match ($this) {
            self::Tecnica => 'Validação técnica',
            self::Aceite => 'Aceite',
        };
    }
}
