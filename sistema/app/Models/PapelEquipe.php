<?php

namespace App\Models;

use App\Models\Concerns\ItemDeDominio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Papel do estudante na equipe do sub-projeto (RF11): Membro Desenvolvedor, Responsável Técnico,
 * Apoio de Campo e outros que o admin cadastrar.
 */
#[Fillable(['nome', 'ativo'])]
class PapelEquipe extends Model
{
    use ItemDeDominio;

    /** Papel sugerido na aprovação da candidatura. */
    public const PADRAO = 'Membro Desenvolvedor';

    protected $table = 'papeis_equipe';
}
