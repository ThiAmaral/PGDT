<?php

namespace App\Models;

use App\Enums\Complexidade;
use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Observers\SolicitacaoObserver;
use Database\Factories\SolicitacaoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Demanda aberta pelo demandante (RF06), classificada pelo coordenador (RF07) e acompanhada
 * pela linha do tempo e pelo chat (RF08).
 */
#[Fillable([
    'projeto_base_id', 'demandante_id', 'escola_instituicao_id', 'titulo', 'descricao', 'justificativa', 'publico_afetado',
    'status', 'motivo_status', 'categoria_id', 'prioridade_id', 'complexidade', 'triado_por', 'triado_em',
])]
#[ObservedBy(SolicitacaoObserver::class)]
class Solicitacao extends Model
{
    /** @use HasFactory<SolicitacaoFactory> */
    use HasFactory;

    protected $table = 'solicitacoes';

    protected $attributes = [
        'status' => 'cadastrada',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusDemanda::class,
            'complexidade' => Complexidade::class,
            'triado_em' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProjetoBase, $this>
     */
    public function projetoBase(): BelongsTo
    {
        return $this->belongsTo(ProjetoBase::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function demandante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'demandante_id');
    }

    /**
     * @return BelongsTo<EscolaInstituicao, $this>
     */
    public function escolaInstituicao(): BelongsTo
    {
        return $this->belongsTo(EscolaInstituicao::class);
    }

    /**
     * @return BelongsTo<CategoriaDemanda, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaDemanda::class);
    }

    /**
     * @return BelongsTo<Prioridade, $this>
     */
    public function prioridade(): BelongsTo
    {
        return $this->belongsTo(Prioridade::class);
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function triador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'triado_por');
    }

    /**
     * @return HasMany<Anexo, $this>
     */
    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class);
    }

    /**
     * Linha do tempo da demanda (RF08), da mais antiga para a mais recente.
     *
     * @return HasMany<Historico, $this>
     */
    public function historicos(): HasMany
    {
        return $this->hasMany(Historico::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Chat da demanda (RF08), da mais antiga para a mais recente.
     *
     * @return HasMany<Mensagem, $this>
     */
    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Demandas que o usuário pode ver: o admin vê todas; o coordenador, as dos projetos
     * que coordena; o demandante, as que abriu.
     *
     * @param  Builder<Solicitacao>  $query
     */
    public function scopeVisiveisPara(Builder $query, Usuario $usuario): void
    {
        match ($usuario->perfil) {
            Perfil::Admin => null,
            Perfil::Coordenador => $query->whereHas('projetoBase', fn (Builder $query) => $query->where('coordenador_id', $usuario->id)),
            Perfil::Demandante => $query->where('demandante_id', $usuario->id),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Filtros da lista: título, status, projeto e prioridade.
     *
     * @param  Builder<Solicitacao>  $query
     * @param  array{busca?: ?string, status?: ?string, projeto?: ?string, prioridade?: ?string}  $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['busca'] ?? null, fn (Builder $query, string $busca) => $query->where('titulo', 'like', "%{$busca}%"))
            ->when($filtros['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filtros['projeto'] ?? null, fn (Builder $query, string $projeto) => $query->where('projeto_base_id', $projeto))
            ->when($filtros['prioridade'] ?? null, fn (Builder $query, string $prioridade) => $query->where('prioridade_id', $prioridade));
    }

    /**
     * Ordem da fila de triagem: prioridade definida pelo admin (sem prioridade por último) e depois as mais antigas.
     *
     * @param  Builder<Solicitacao>  $query
     */
    public function scopeOrdemDaFila(Builder $query): void
    {
        $query
            ->leftJoin('prioridades', 'prioridades.id', '=', 'solicitacoes.prioridade_id')
            ->select('solicitacoes.*')
            ->orderByRaw('prioridades.ordem is null')
            ->orderBy('prioridades.ordem')
            ->orderBy('solicitacoes.created_at')
            ->orderBy('solicitacoes.id');
    }

    public function foiTriada(): bool
    {
        return $this->categoria_id !== null && $this->prioridade_id !== null && $this->complexidade !== null;
    }

    /**
     * Etapa da barra de progresso. Recusada ou cancelada, a demanda fica na etapa em que estava
     * antes, conforme o histórico.
     */
    public function etapaDoProgresso(): int
    {
        if ($this->status->etapa() !== null) {
            return $this->status->etapa();
        }

        $anterior = $this->historicos
            ->where('tipo', TipoHistorico::Status)
            ->last(fn (Historico $historico) => ($historico->dados['para'] ?? null) === $this->status->value);

        return StatusDemanda::tryFrom($anterior?->dados['de'] ?? '')?->etapa() ?? 0;
    }
}
