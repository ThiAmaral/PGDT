<?php

namespace App\Enums;

enum StatusUsuario: string
{
    case Pendente = 'pendente';
    case Ativo = 'ativo';
    case Inativo = 'inativo';
    case Rejeitado = 'rejeitado';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Ativo => 'Ativo',
            self::Inativo => 'Inativo',
            self::Rejeitado => 'Rejeitado',
        };
    }

    /**
     * Classe de cor do Bootstrap usada no badge do status.
     */
    public function cor(): string
    {
        return match ($this) {
            self::Pendente => 'warning',
            self::Ativo => 'success',
            self::Inativo => 'secondary',
            self::Rejeitado => 'danger',
        };
    }

    /**
     * Status que exigem um motivo ao serem aplicados.
     */
    public function exigeMotivo(): bool
    {
        return in_array($this, [self::Inativo, self::Rejeitado], true);
    }

    /**
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->rotulo()])->all();
    }
}
