<?php

namespace App\Models;

use App\Observers\MembroEquipeObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estudante na equipe do sub-projeto (RF11). A remoção preenche `removido_em` e não apaga o registro.
 */
#[Fillable(['subprojeto_id', 'estudante_id', 'papel_id', 'candidatura_id', 'removido_em'])]
#[ObservedBy(MembroEquipeObserver::class)]
class MembroEquipe extends Model
{
    protected $table = 'membros_equipe';

    protected function casts(): array
    {
        return ['removido_em' => 'datetime'];
    }

    /**
     * @return BelongsTo<Subprojeto, $this>
     */
    public function subprojeto(): BelongsTo
    {
        return $this->belongsTo(Subprojeto::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function estudante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudante_id');
    }

    /**
     * @return BelongsTo<PapelEquipe, $this>
     */
    public function papel(): BelongsTo
    {
        return $this->belongsTo(PapelEquipe::class, 'papel_id');
    }

    /**
     * @return BelongsTo<Candidatura, $this>
     */
    public function candidatura(): BelongsTo
    {
        return $this->belongsTo(Candidatura::class);
    }

    /**
     * Membros que continuam na equipe.
     *
     * @param  Builder<MembroEquipe>  $query
     */
    public function scopeAtivos(Builder $query): void
    {
        $query->whereNull('removido_em');
    }

    public function foiRemovido(): bool
    {
        return $this->removido_em !== null;
    }
}
