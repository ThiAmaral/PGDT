<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * Arquivo enviado na abertura da demanda (RF06). Fica no disco "local", fora da pasta pública,
 * e só é baixado por quem pode ver a demanda.
 */
#[Fillable(['solicitacao_id', 'nome_original', 'caminho', 'mime', 'tamanho_bytes', 'enviado_por'])]
class Anexo extends Model
{
    /** Tipos aceitos na abertura da demanda. */
    public const EXTENSOES = ['pdf', 'jpg', 'jpeg', 'png'];

    /** Tamanho máximo de cada arquivo, em KB (5 MB). */
    public const TAMANHO_MAXIMO_KB = 5120;

    /** Quantidade máxima de arquivos por demanda. */
    public const QUANTIDADE_MAXIMA = 5;

    protected $table = 'anexos';

    protected function casts(): array
    {
        return ['tamanho_bytes' => 'integer'];
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
    public function remetente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'enviado_por');
    }

    /**
     * Tamanho no formato brasileiro, ex.: "850 KB" ou "1,4 MB".
     */
    public function tamanhoLegivel(): string
    {
        return match (true) {
            $this->tamanho_bytes < 1024 => "{$this->tamanho_bytes} B",
            $this->tamanho_bytes < 1024 * 1024 => Number::format($this->tamanho_bytes / 1024, maxPrecision: 0, locale: 'pt_BR').' KB',
            default => Number::format($this->tamanho_bytes / (1024 * 1024), maxPrecision: 1, locale: 'pt_BR').' MB',
        };
    }
}
