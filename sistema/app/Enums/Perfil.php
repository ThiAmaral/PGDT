<?php

namespace App\Enums;

enum Perfil: string
{
    case Admin = 'admin';
    case Coordenador = 'coordenador';
    case Demandante = 'demandante';
    case Estudante = 'estudante';

    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Coordenador => 'Coordenador projeto de extensão',
            self::Demandante => 'Demandante',
            self::Estudante => 'Estudante Extensionista',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $perfil) => [$perfil->value => $perfil->rotulo()])->all();
    }
}
