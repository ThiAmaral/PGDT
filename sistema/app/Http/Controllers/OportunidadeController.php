<?php

namespace App\Http\Controllers;

use App\Enums\Modalidade;
use App\Models\ProjetoBase;
use App\Models\Subprojeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RF10: vitrine de oportunidades. Cards dos sub-projetos com vagas, busca, filtro por modalidade
 * e candidatura com um clique.
 */
class OportunidadeController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = self::validarFiltros($request);

        return view('oportunidades.index', [
            'subprojetos' => Subprojeto::query()
                ->with(['solicitacao.projetoBase', 'coordenador'])
                ->withCount('membrosAtivos')
                ->comVagasAbertas()
                ->filtrar($filtros)
                ->latest()
                ->orderByDesc('id')
                ->paginate(12)
                ->withQueryString(),
            'filtros' => $filtros,
            'modalidades' => Modalidade::cases(),
            'projetos' => ProjetoBase::query()->disponiveis()->get(),
        ]);
    }

    /**
     * Destino do botão "Quero participar" da página pública: depois do login, o estudante volta
     * para cá e conclui a inscrição com um clique.
     */
    public function show(Request $request, Subprojeto $subprojeto): View
    {
        $subprojeto->load(['solicitacao.projetoBase.coordenador', 'coordenador'])->loadCount('membrosAtivos');
        $usuario = $request->user();
        $candidatura = $subprojeto->candidaturaDe($usuario);

        // Sub-projeto fechado só aparece para quem já tem candidatura ou acompanha a demanda.
        abort_unless($subprojeto->recebeCandidaturas() || $candidatura || Gate::allows('view', $subprojeto), 404);

        return view('oportunidades.show', [
            'subprojeto' => $subprojeto,
            'candidatura' => $candidatura,
            'impedimento' => $subprojeto->impedimentoDeCandidatura($usuario),
        ]);
    }

    /**
     * Filtros comuns à vitrine e à página pública.
     *
     * @return array{busca?: ?string, modalidade?: ?string, projeto?: ?string}
     */
    public static function validarFiltros(Request $request): array
    {
        return $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'modalidade' => ['nullable', Rule::enum(Modalidade::class)],
            'projeto' => ['nullable', 'integer'],
        ]);
    }
}
