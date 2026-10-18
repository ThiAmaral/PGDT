<?php

namespace App\Models;

use App\Enums\Perfil;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['email', 'perfil', 'token_hash', 'criado_por', 'expira_em', 'usado_em'])]
class Convite extends Model
{
    /** Validade do link do convite, em dias. */
    public const VALIDADE_DIAS = 7;

    protected function casts(): array
    {
        return [
            'perfil' => Perfil::class,
            'expira_em' => 'datetime',
            'usado_em' => 'datetime',
        ];
    }

    /**
     * Cria um convite e devolve também o token puro, que só vai no e-mail.
     *
     * @return array{0: Convite, 1: string}
     */
    public static function gerar(string $email, Usuario $criador, Perfil $perfil = Perfil::Coordenador): array
    {
        $token = Str::random(64);

        $convite = self::create([
            'email' => $email,
            'perfil' => $perfil,
            'token_hash' => hash('sha256', $token),
            'criado_por' => $criador->id,
            'expira_em' => now()->addDays(self::VALIDADE_DIAS),
        ]);

        return [$convite, $token];
    }

    public static function buscarValidoPorToken(string $token): ?self
    {
        return self::query()->validos()->where('token_hash', hash('sha256', $token))->first();
    }

    /**
     * @param  Builder<Convite>  $query
     */
    public function scopeValidos(Builder $query): void
    {
        $query->whereNull('usado_em')->where('expira_em', '>', now());
    }

    public function situacao(): string
    {
        if ($this->usado_em) {
            return 'Usado';
        }

        return $this->expira_em->isPast() ? 'Expirado' : 'Válido';
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
