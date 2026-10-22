<?php

namespace App\Enums;

/**
 * Complexidade da demanda, definida pelo coordenador na triagem (RF07).
 */
enum Complexidade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';

    public function rotulo(): string
    {
        return match ($this) {
            self::Baixa => 'Baixa',
            self::Media => 'Média',
            self::Alta => 'Alta',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $complexidade) => [$complexidade->value => $complexidade->rotulo()])->all();
    }
}
