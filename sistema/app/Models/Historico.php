<?php

namespace App\Models;

use App\Enums\TipoHistorico;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Registro da linha do tempo da demanda (RF08). Somente inclusão: nunca é editado nem apagado.
 */
#[Fillable(['solicitacao_id', 'usuario_id', 'tipo', 'descricao', 'dados'])]
class Historico extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historicos';

    protected function casts(): array
    {
        return [
            'tipo' => TipoHistorico::class,
            'dados' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('O histórico da demanda não pode ser editado.'));
        static::deleting(fn () => throw new LogicException('O histórico da demanda não pode ser apagado.'));
    }

    /**
     * @return BelongsTo<Solicitacao, $this>
     */
    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(Solicitacao::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
