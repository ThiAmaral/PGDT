<?php

namespace App\Models;

use App\Enums\Perfil;
use App\Observers\MensagemObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comentário no chat da demanda (RF08). Não é editado nem apagado, para a conversa servir de registro.
 */
#[Fillable(['solicitacao_id', 'usuario_id', 'perfil', 'texto'])]
#[ObservedBy(MensagemObserver::class)]
class Mensagem extends Model
{
    public const UPDATED_AT = null;

    /** Tamanho máximo de cada comentário. */
    public const TAMANHO_MAXIMO = 2000;

    protected $table = 'mensagens';

    protected function casts(): array
    {
        return ['perfil' => Perfil::class];
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
    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
