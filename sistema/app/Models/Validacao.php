<?php

namespace App\Models;

use App\Enums\DecisaoValidacao;
use App\Enums\EtapaValidacao;
use App\Observers\ValidacaoObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Decisão de uma etapa da validação da entrega (RF13). Somente inclusão: vale como registro.
 */
#[Fillable(['entrega_id', 'etapa', 'decisao', 'feedback', 'usuario_id'])]
#[ObservedBy(ValidacaoObserver::class)]
class Validacao extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'validacoes';

    protected function casts(): array
    {
        return [
            'etapa' => EtapaValidacao::class,
            'decisao' => DecisaoValidacao::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('A validação da entrega não pode ser editada.'));
        static::deleting(fn () => throw new LogicException('A validação da entrega não pode ser apagada.'));
    }

    /**
     * @return BelongsTo<Entrega, $this>
     */
    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
