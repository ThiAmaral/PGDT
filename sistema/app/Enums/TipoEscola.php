<?php

namespace App\Enums;

enum TipoEscola: string
{
    case EscolaPublica = 'escola_publica';
    case EscolaPrivada = 'escola_privada';
    case Instituicao = 'instituicao';
    case Outro = 'outro';

    public function rotulo(): string
    {
        return match ($this) {
            self::EscolaPublica => 'Escola pública',
            self::EscolaPrivada => 'Escola privada',
            self::Instituicao => 'Instituição',
            self::Outro => 'Outro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $tipo) => [$tipo->value => $tipo->rotulo()])->all();
    }
}
