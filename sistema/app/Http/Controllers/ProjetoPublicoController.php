<?php

namespace App\Http\Controllers;

use App\Enums\Modalidade;
use App\Models\ProjetoBase;
use App\Models\Subprojeto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * RF14: consulta pública, sem login, dos projetos de extensão e dos sub-projetos com vagas.
 * Mostra só dados públicos: contatos do demandante, anexos e chat ficam de fora (LGPD).
 */
class ProjetoPublicoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = OportunidadeController::validarFiltros($request);

        $projetos = ProjetoBase::query()
            ->with(['tipologia', 'coordenador'])
            ->withCount(['solicitacoes as vagas_abertas_count' => fn (Builder $query) => $query
                ->whereHas('subprojeto', fn (Builder $query) => $query->comVagasAbertas())])
            ->visiveisAoPublico()
            ->orderBy('titulo')
            ->get();

        return view('publico.index', [
            'subprojetos' => $this->subprojetosAbertos($filtros)->paginate(12)->withQueryString(),
            'projetos' => $projetos,
            'filtros' => $filtros,
            'modalidades' => Modalidade::cases(),
        ]);
    }

    public function show(ProjetoBase $projetoBase): View
    {
        abort_unless($projetoBase->ativo && $projetoBase->publico, 404);

        $projetoBase->load(['tipologia', 'coordenador.coordenador']);

        return view('publico.show', [
            'projeto' => $projetoBase,
            'subprojetos' => $this->subprojetosAbertos(['projeto' => (string) $projetoBase->id])->get(),
        ]);
    }

    /**
     * @param  array{busca?: ?string, modalidade?: ?string, projeto?: ?string}  $filtros
     * @return Builder<Subprojeto>
     */
    private function subprojetosAbertos(array $filtros): Builder
    {
        return Subprojeto::query()
            ->with(['solicitacao.projetoBase', 'coordenador'])
            ->withCount('membrosAtivos')
            ->comVagasAbertas()
            ->publicos()
            ->filtrar($filtros)
            ->latest()
            ->orderByDesc('id');
    }
}
