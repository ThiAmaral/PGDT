<?php

namespace App\Http\Controllers;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Models\MembroEquipe;
use App\Models\ProjetoBase;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RF15: relatório de demandas executadas por extensionista. Os valores vêm do sub-projeto (RF09),
 * então uma correção da carga horária já aparece no relatório. Membros removidos da equipe não contam.
 */
class RelatorioController extends Controller
{
    /** Valor do filtro de status que traz todas as demandas. */
    public const TODOS_OS_STATUS = 'todos';

    public function extensionistas(Request $request): View
    {
        [$filtros, $participacoes] = $this->consultar($request);

        return view('relatorios.extensionistas', [
            'filtros' => $filtros,
            'porEstudante' => $this->agruparPorEstudante($participacoes),
            'estudantes' => $this->estudantesVisiveis($request->user()),
            'projetos' => $this->projetosVisiveis($request->user()),
            'ehEstudante' => $request->user()->temPerfil(Perfil::Estudante),
        ]);
    }

    public function extensionistasPdf(Request $request): Response
    {
        [$filtros, $participacoes] = $this->consultar($request);

        $pdf = Pdf::loadView('relatorios.extensionistas-pdf', [
            'filtros' => $filtros,
            'porEstudante' => $this->agruparPorEstudante($participacoes),
            // O título do projeto só aparece se ele estiver entre os que o usuário vê.
            'projeto' => isset($filtros['projeto']) ? $this->projetosVisiveis($request->user())->firstWhere('id', $filtros['projeto']) : null,
            'geradoPor' => $request->user(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-extensionistas-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Filtros: estudante, período (sub-projetos que começaram e terminaram entre as datas), Projeto Base e status.
     * Por padrão, só as demandas "Concluída".
     *
     * @return array{0: array<string, mixed>, 1: Collection<int, MembroEquipe>}
     */
    private function consultar(Request $request): array
    {
        $usuario = $request->user();
        abort_unless($usuario->temPerfil(Perfil::Admin, Perfil::Coordenador, Perfil::Estudante), 403);

        $filtros = $request->validate([
            'estudante' => ['nullable', 'integer'],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'projeto' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in([self::TODOS_OS_STATUS, ...array_column(StatusDemanda::cases(), 'value')])],
        ], [
            'ate.after_or_equal' => 'A data final não pode ser anterior à inicial.',
        ]);

        $filtros['status'] ??= StatusDemanda::Concluida->value;

        // O estudante vê só o próprio relatório, qualquer que seja o filtro enviado.
        if ($usuario->temPerfil(Perfil::Estudante)) {
            $filtros['estudante'] = $usuario->id;
        }

        $participacoes = MembroEquipe::query()
            ->ativos()
            ->with(['estudante.estudante.curso', 'papel', 'subprojeto.solicitacao.projetoBase', 'subprojeto.solicitacao.escolaInstituicao'])
            ->whereHas('subprojeto', function (Builder $query) use ($usuario, $filtros): void {
                $query->visiveisPara($usuario)
                    ->when($filtros['de'] ?? null, fn (Builder $query, string $de) => $query->whereDate('data_inicio', '>=', $de))
                    ->when($filtros['ate'] ?? null, fn (Builder $query, string $ate) => $query->whereDate('data_fim', '<=', $ate))
                    ->whereHas('solicitacao', fn (Builder $query) => $query
                        ->when($filtros['projeto'] ?? null, fn (Builder $query, int|string $projeto) => $query->where('projeto_base_id', $projeto))
                        ->when($filtros['status'] !== self::TODOS_OS_STATUS, fn (Builder $query) => $query->where('status', $filtros['status'])));
            })
            ->when($filtros['estudante'] ?? null, fn (Builder $query, int|string $estudante) => $query->where('estudante_id', $estudante))
            ->get();

        return [$filtros, $participacoes];
    }

    /**
     * Uma seção por estudante, em ordem alfabética, com os sub-projetos pela data de início.
     *
     * @param  Collection<int, MembroEquipe>  $participacoes
     * @return Collection<int, array{estudante: Usuario, participacoes: Collection<int, MembroEquipe>, total: int}>
     */
    private function agruparPorEstudante(Collection $participacoes): Collection
    {
        return $participacoes
            ->groupBy('estudante_id')
            ->map(fn (Collection $doEstudante) => [
                'estudante' => $doEstudante->first()->estudante,
                'participacoes' => $doEstudante->sortBy(fn (MembroEquipe $membro) => $membro->subprojeto->data_inicio)->values(),
                'total' => $doEstudante->sum(fn (MembroEquipe $membro) => $membro->subprojeto->carga_horaria_estimada),
            ])
            ->sortBy(fn (array $secao) => mb_strtolower($secao['estudante']->nome))
            ->values();
    }

    /**
     * Estudantes que aparecem no filtro: os que estão ou estiveram nas equipes visíveis ao usuário.
     *
     * @return Collection<int, Usuario>
     */
    private function estudantesVisiveis(Usuario $usuario): Collection
    {
        if ($usuario->temPerfil(Perfil::Estudante)) {
            return collect();
        }

        return Usuario::query()
            ->whereHas('participacoes', fn (Builder $query) => $query
                ->ativos()
                ->whereHas('subprojeto', fn (Builder $query) => $query->visiveisPara($usuario)))
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }

    /**
     * @return Collection<int, ProjetoBase>
     */
    private function projetosVisiveis(Usuario $usuario): Collection
    {
        return ProjetoBase::query()
            ->whereHas('solicitacoes.subprojeto', fn (Builder $query) => $query->visiveisPara($usuario))
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }
}
