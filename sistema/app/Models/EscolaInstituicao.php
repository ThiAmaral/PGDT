<?php

namespace App\Models;

use App\Enums\TipoEscola;
use Database\Factories\EscolaInstituicaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Escola digitada pelo demandante no cadastro nasce com `ativo = false`
 * e é conferida e ativada pelo admin junto com a aprovação do cadastro.
 */
#[Fillable(['nome', 'tipo', 'codigo_inep', 'cidade', 'uf', 'telefone', 'email', 'ativo'])]
class EscolaInstituicao extends Model
{
    /** @use HasFactory<EscolaInstituicaoFactory> */
    use HasFactory;

    protected $table = 'escolas_instituicoes';

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'tipo' => TipoEscola::class,
        ];
    }

    /**
     * @return HasMany<Demandante, $this>
     */
    public function demandantes(): HasMany
    {
        return $this->hasMany(Demandante::class);
    }

    /**
     * @param  Builder<EscolaInstituicao>  $query
     */
    public function scopeAtivas(Builder $query): void
    {
        $query->where('ativo', true)->orderBy('nome');
    }

    public function nomeCompleto(): string
    {
        return "{$this->nome} — {$this->cidade}/{$this->uf}";
    }
}
