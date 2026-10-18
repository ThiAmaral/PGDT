<?php

namespace App\Models;

use Database\Factories\CursoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nome', 'ativo'])]
class Curso extends Model
{
    /** @use HasFactory<CursoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    /**
     * @return HasMany<Estudante, $this>
     */
    public function estudantes(): HasMany
    {
        return $this->hasMany(Estudante::class);
    }

    /**
     * @param  Builder<Curso>  $query
     */
    public function scopeAtivos(Builder $query): void
    {
        $query->where('ativo', true)->orderBy('nome');
    }
}
