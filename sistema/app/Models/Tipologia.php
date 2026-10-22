<?php

namespace App\Models;

use App\Models\Concerns\ItemDeDominio;
use Database\Factories\TipologiaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipologia da ação de extensão (Programa, Projeto, Curso...).
 */
#[Fillable(['nome', 'ativo'])]
class Tipologia extends Model
{
    /** @use HasFactory<TipologiaFactory> */
    use HasFactory, ItemDeDominio;

    protected $table = 'tipologias';

    /**
     * @return HasMany<ProjetoBase, $this>
     */
    public function projetosBase(): HasMany
    {
        return $this->hasMany(ProjetoBase::class);
    }
}
