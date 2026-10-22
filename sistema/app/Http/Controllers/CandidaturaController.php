<?php

namespace App\Http\Controllers;

use App\Enums\Perfil;
use App\Enums\StatusCandidatura;
use App\Models\Candidatura;
use App\Models\MembroEquipe;
use App\Models\Subprojeto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * RF10: o estudante se candidata com um clique. RF11: a coordenação aprova (com o papel) ou recusa.
 */
class CandidaturaController extends Controller
{
    public function store(Request $request, Subprojeto $subprojeto): RedirectResponse
    {
        Gate::authorize('candidatar', $subprojeto);

        // Quem cancelou a candidatura pode se candidatar de novo: a mesma linha volta a ficar pendente.
        Candidatura::updateOrCreate(
            ['subprojeto_id' => $subprojeto->id, 'estudante_id' => $request->user()->id],
            ['status' => StatusCandidatura::Pendente, 'avaliado_por' => null, 'avaliado_em' => null],
        );

        return redirect()->route('oportunidades.show', $subprojeto)
            ->with('sucesso', 'Candidatura enviada. A coordenação vai avaliar e você acompanha a situação em "Meus sub-projetos".');
    }

    /**
     * O próprio estudante desiste enquanto a candidatura não foi avaliada.
     */
    public function cancelar(Request $request, Subprojeto $subprojeto, Candidatura $candidatura): RedirectResponse
    {
        abort_unless($candidatura->estudante_id === $request->user()->id, 403);

        if (! $candidatura->estaPendente()) {
            return back()->withErrors(['candidatura' => 'Esta candidatura já foi avaliada e não pode ser cancelada.']);
        }

        $candidatura->update(['status' => StatusCandidatura::Cancelada]);

        return back()->with('sucesso', 'Candidatura cancelada.');
    }

    /**
     * Aprova a candidatura e coloca o estudante na equipe com o papel escolhido.
     */
    public function aprovar(Request $request, Subprojeto $subprojeto, Candidatura $candidatura): RedirectResponse
    {
        Gate::authorize('gerenciarEquipe', $subprojeto);

        $dados = $request->validateWithBag("candidatura{$candidatura->id}", [
            'papel_id' => ['required', Rule::exists('papeis_equipe', 'id')->where('ativo', true)],
        ], [], ['papel_id' => 'papel']);

        DB::transaction(function () use ($request, $subprojeto, $candidatura, $dados): void {
            // Trava o sub-projeto para duas aprovações ao mesmo tempo não passarem do número de vagas.
            $subprojeto = Subprojeto::query()->lockForUpdate()->findOrFail($subprojeto->id);
            $candidatura->refresh();

            $erro = match (true) {
                ! $candidatura->estaPendente() => 'Esta candidatura já foi avaliada.',
                ! $candidatura->estudante->temPerfil(Perfil::Estudante) || ! $candidatura->estudante->estaAtivo() => 'A conta deste estudante não está ativa. Recuse a candidatura.',
                $subprojeto->vagasLivres() === 0 => 'A equipe está completa. Aumente o número de vagas ou recuse a candidatura.',
                default => null,
            };

            if ($erro !== null) {
                throw ValidationException::withMessages(['candidatura' => $erro]);
            }

            $candidatura->update([
                'status' => StatusCandidatura::Aprovada,
                'avaliado_por' => $request->user()->id,
                'avaliado_em' => now(),
            ]);

            MembroEquipe::create([
                'subprojeto_id' => $subprojeto->id,
                'estudante_id' => $candidatura->estudante_id,
                'papel_id' => $dados['papel_id'],
                'candidatura_id' => $candidatura->id,
            ]);
        });

        return redirect()->route('subprojetos.show', $subprojeto)
            ->with('sucesso', "{$candidatura->estudante->nome} entrou na equipe.");
    }

    public function recusar(Request $request, Subprojeto $subprojeto, Candidatura $candidatura): RedirectResponse
    {
        Gate::authorize('gerenciarEquipe', $subprojeto);

        if (! $candidatura->estaPendente()) {
            return back()->withErrors(['candidatura' => 'Esta candidatura já foi avaliada.']);
        }

        $candidatura->update([
            'status' => StatusCandidatura::Recusada,
            'avaliado_por' => $request->user()->id,
            'avaliado_em' => now(),
        ]);

        return redirect()->route('subprojetos.show', $subprojeto)
            ->with('sucesso', "Candidatura de {$candidatura->estudante->nome} recusada.");
    }
}
