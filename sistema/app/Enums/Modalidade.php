<?php

namespace App\Enums;

/**
 * Modalidade do sub-projeto (decisão da Fase 0): cada demanda tem a sua necessidade.
 */
enum Modalidade: string
{
    case Remoto = 'remoto';
    case Presencial = 'presencial';
    case Hibrido = 'hibrido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Remoto => 'Remoto',
            self::Presencial => 'Presencial',
            self::Hibrido => 'Híbrido',
        };
    }

    /**
     * Classe de cor do Bootstrap usada no badge da modalidade.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Remoto => 'info',
            self::Presencial => 'success',
            self::Hibrido => 'primary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $modalidade) => [$modalidade->value => $modalidade->rotulo()])->all();
    }
}
