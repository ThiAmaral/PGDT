<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Comportamento comum das tabelas de domínio (tipologias, categorias, prioridades):
 * o admin inativa um item em vez de apagar, para não quebrar os registros que já o usam.
 */
trait ItemDeDominio
{
    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAtivos(Builder $query): void
    {
        $query->where('ativo', true)->ordenados();
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdenados(Builder $query): void
    {
        $query->orderBy('nome');
    }
}
