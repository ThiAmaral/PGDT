<?php

namespace App\Models;

use App\Enums\DecisaoValidacao;
use App\Enums\SituacaoEntrega;
use App\Enums\TipoEntrega;
use App\Observers\EntregaObserver;
use Database\Factories\EntregaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Entrega da equipe do sub-projeto (RF12), validada em duas etapas (RF13): primeiro o coordenador,
 * depois o demandante. A situação acompanha a última decisão. Várias entregas podem estar em
 * avaliação ao mesmo tempo; o número dá a ordem dentro do sub-projeto.
 */
#[Fillable(['subprojeto_id', 'numero', 'enviado_por', 'tipo', 'titulo', 'descricao', 'url', 'situacao'])]
#[ObservedBy(EntregaObserver::class)]
class Entrega extends Model
{
    /** @use HasFactory<EntregaFactory> */
    use HasFactory;

    /** Tamanho máximo de cada arquivo, em KB (20 MB, decisão da Fase 0). */
    public const TAMANHO_MAXIMO_KB = 20480;

    /** Quantidade máxima de arquivos por entrega. */
    public const QUANTIDADE_MAXIMA = 5;

    protected $table = 'entregas';

    protected $attributes = [
        'situacao' => 'aguardando_tecnica',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'tipo' => TipoEntrega::class,
            'situacao' => SituacaoEntrega::class,
        ];
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
    public function remetente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'enviado_por');
    }

    /**
     * @return HasMany<EntregaArquivo, $this>
     */
    public function arquivos(): HasMany
    {
        return $this->hasMany(EntregaArquivo::class);
    }

    /**
     * @return HasMany<Validacao, $this>
     */
    public function validacoes(): HasMany
    {
        return $this->hasMany(Validacao::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Entregas que o usuário vê: o demandante só as que já passaram pela aprovação técnica.
     *
     * @param  Builder<Entrega>  $query
     */
    public function scopeVisiveisAoDemandante(Builder $query): void
    {
        $query->whereIn('situacao', array_map(
            fn (SituacaoEntrega $situacao) => $situacao->value,
            array_filter(SituacaoEntrega::cases(), fn (SituacaoEntrega $situacao) => $situacao->passouPelaEtapaTecnica()),
        ));
    }

    /**
     * Grava a decisão e a nova situação juntas (RF13). O ValidacaoObserver registra na linha do tempo.
     */
    public function registrarValidacao(DecisaoValidacao $decisao, ?string $feedback, Usuario $usuario): Validacao
    {
        return DB::transaction(function () use ($decisao, $feedback, $usuario): Validacao {
            $validacao = $this->validacoes()->create([
                'etapa' => $decisao->etapa(),
                'decisao' => $decisao,
                'feedback' => $feedback,
                'usuario_id' => $usuario->id,
            ]);

            $this->updateOrFail(['situacao' => $decisao->situacaoResultante()]);

            return $validacao;
        });
    }

    /**
     * Nome curto usado nas listas e na linha do tempo, ex.: "Entrega nº 2".
     */
    public function rotulo(): string
    {
        return "Entrega nº {$this->numero}";
    }

    /**
     * Enviada pelo coordenador ou pelo admin em nome da equipe (ex.: material recebido por e-mail).
     */
    public function enviadaPelaCoordenacao(): bool
    {
        return ! $this->subprojeto->membros()->where('estudante_id', $this->enviado_por)->exists();
    }

    /**
     * Última decisão com feedback, para mostrar à equipe o que ajustar.
     */
    public function ultimoFeedback(): ?Validacao
    {
        return $this->validacoes->filter(fn (Validacao $validacao) => $validacao->feedback !== null)->last();
    }
}
