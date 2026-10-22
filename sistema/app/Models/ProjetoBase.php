<?php

namespace App\Models;

use Database\Factories\ProjetoBaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Projeto de extensão (RF05). As demandas são abertas dentro de um Projeto Base.
 */
#[Fillable(['titulo', 'descricao', 'escopo_preliminar', 'data_inicio', 'data_fim', 'tipologia_id', 'coordenador_id', 'publico', 'ativo'])]
class ProjetoBase extends Model
{
    /** @use HasFactory<ProjetoBaseFactory> */
    use HasFactory;

    public const DEMANDAS_GERAIS = 'Demandas Gerais';

    protected $table = 'projetos_base';

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'publico' => 'boolean',
            'ativo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Tipologia, $this>
     */
    public function tipologia(): BelongsTo
    {
        return $this->belongsTo(Tipologia::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function coordenador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'coordenador_id');
    }

    /**
     * @return HasMany<Solicitacao, $this>
     */
    public function solicitacoes(): HasMany
    {
        return $this->hasMany(Solicitacao::class);
    }

    /**
     * Projetos que aparecem na página pública (RF14): ativos e não ocultados pelo coordenador.
     *
     * @param  Builder<ProjetoBase>  $query
     */
    public function scopeVisiveisAoPublico(Builder $query): void
    {
        $query->where('ativo', true)->where('publico', true);
    }

    /**
     * Projetos que podem receber demandas (seleção do demandante na Fase 3).
     *
     * @param  Builder<ProjetoBase>  $query
     */
    public function scopeDisponiveis(Builder $query): void
    {
        $query->where('ativo', true)->orderBy('titulo');
    }

    /**
     * Filtros da listagem: título, tipologia, situação e "só os meus".
     *
     * @param  Builder<ProjetoBase>  $query
     * @param  array{busca?: ?string, tipologia?: ?string, situacao?: ?string, meus?: ?string}  $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros, Usuario $usuario): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $query, string $busca) => $query->where('titulo', 'like', "%{$busca}%"))
            ->when($filtros['tipologia'] ?? null, fn (Builder $query, string $tipologia) => $query->where('tipologia_id', $tipologia))
            ->when(($filtros['situacao'] ?? null) === 'ativos', fn (Builder $query) => $query->where('ativo', true))
            ->when(($filtros['situacao'] ?? null) === 'inativos', fn (Builder $query) => $query->where('ativo', false))
            ->when($filtros['meus'] ?? null, fn (Builder $query) => $query->where('coordenador_id', $usuario->id));
    }

    public function periodo(): string
    {
        $inicio = $this->data_inicio->format('d/m/Y');

        return $this->data_fim ? "{$inicio} a {$this->data_fim->format('d/m/Y')}" : "Desde {$inicio}";
    }
}
