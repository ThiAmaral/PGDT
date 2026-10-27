<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

/**
 * Arquivo de uma entrega (RF12). Fica no disco "local", fora da pasta pública,
 * e só é baixado por quem pode ver a entrega.
 */
#[Fillable(['entrega_id', 'nome_original', 'caminho', 'mime', 'tamanho_bytes'])]
class EntregaArquivo extends Model
{
    protected $table = 'entrega_arquivos';

    protected function casts(): array
    {
        return ['tamanho_bytes' => 'integer'];
    }

    /**
     * @return BelongsTo<Entrega, $this>
     */
    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class);
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
