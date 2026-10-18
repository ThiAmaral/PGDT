<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'escola_instituicao_id', 'cargo'])]
class Demandante extends Model
{
    protected $primaryKey = 'usuario_id';

    public $incrementing = false;

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * @return BelongsTo<EscolaInstituicao, $this>
     */
    public function escolaInstituicao(): BelongsTo
    {
        return $this->belongsTo(EscolaInstituicao::class);
    }
}
