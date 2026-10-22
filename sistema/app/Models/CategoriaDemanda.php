<?php

namespace App\Models;

use App\Models\Concerns\ItemDeDominio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Categoria definida pelo coordenador na triagem da demanda (RF07).
 */
#[Fillable(['nome', 'ativo'])]
class CategoriaDemanda extends Model
{
    use ItemDeDominio;

    protected $table = 'categorias_demanda';
}
