<?php

namespace App\Models;

use App\Models\Concerns\ItemDeDominio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Prioridade definida pelo coordenador na triagem da demanda (RF07).
 */
#[Fillable(['nome', 'ordem', 'ativo'])]
class Prioridade extends Model
{
    use ItemDeDominio;

    protected $table = 'prioridades';

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'ordem' => 'integer'];
    }

    /**
     * A fila de triagem segue a ordem definida pelo admin, e não o nome.
     *
     * @param  Builder<Prioridade>  $query
     */
    public function scopeOrdenados(Builder $query): void
    {
        $query->orderBy('ordem')->orderBy('nome');
    }
}
