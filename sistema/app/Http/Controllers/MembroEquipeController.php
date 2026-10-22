<?php

namespace App\Http\Controllers;

use App\Models\MembroEquipe;
use App\Models\Subprojeto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * RF11: troca de papel e remoção de membros da equipe. A remoção não apaga o registro.
 */
class MembroEquipeController extends Controller
{
    public function alterarPapel(Request $request, Subprojeto $subprojeto, MembroEquipe $membro): RedirectResponse
    {
        Gate::authorize('gerenciarEquipe', $subprojeto);
        abort_if($membro->foiRemovido(), 404);

        $dados = $request->validateWithBag("membro{$membro->id}", [
            // O papel atual continua válido mesmo que o admin o tenha inativado depois.
            'papel_id' => ['required', Rule::exists('papeis_equipe', 'id')->where(
                fn ($query) => $query->where('ativo', true)->orWhere('id', $membro->papel_id)
            )],
        ], [], ['papel_id' => 'papel']);

        $membro->update($dados);

        return redirect()->route('subprojetos.show', $subprojeto)
            ->with('sucesso', "Papel de {$membro->estudante->nome} atualizado.");
    }

    public function remover(Subprojeto $subprojeto, MembroEquipe $membro): RedirectResponse
    {
        Gate::authorize('gerenciarEquipe', $subprojeto);
        abort_if($membro->foiRemovido(), 404);

        $membro->update(['removido_em' => now()]);

        return redirect()->route('subprojetos.show', $subprojeto)
            ->with('sucesso', "{$membro->estudante->nome} foi removido(a) da equipe.");
    }
}
