<?php

namespace App\Http\Controllers;

use App\Enums\StatusDemanda;
use App\Enums\TipoHistorico;
use App\Models\Historico;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dashboard gerencial (Fase 7): demandas por status, categoria, escola e Projeto Base, e os tempos
 * médios de atendimento. O admin vê todas as demandas; o coordenador, as que ele pode ver (RF07).
 */
class DashboardController extends Controller
{
    /** Quantas escolas aparecem no gráfico; as demais somam em "Outras". */
    private const ESCOLAS_NO_GRAFICO = 10;

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $filtros = $request->validate([
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'projeto' => ['nullable', 'integer'],
        ], [
            'ate.after_or_equal' => 'A data final não pode ser anterior à inicial.',
        ]);

        $demandas = Solicitacao::query()
            ->visiveisPara($usuario)
            ->when($filtros['de'] ?? null, fn (Builder $query, string $de) => $query->whereDate('created_at', '>=', $de))
            ->when($filtros['ate'] ?? null, fn (Builder $query, string $ate) => $query->whereDate('created_at', '<=', $ate))
            ->when($filtros['projeto'] ?? null, fn (Builder $query, string $projeto) => $query->where('projeto_base_id', $projeto))
            ->with([
                'categoria:id,nome',
                'escolaInstituicao:id,nome',
                'projetoBase:id,titulo',
                'historicos' => fn ($query) => $query->where('tipo', TipoHistorico::Status),
            ])
            ->get();

        $porStatus = collect(StatusDemanda::cases())
            ->mapWithKeys(fn (StatusDemanda $status) => [$status->rotulo() => $demandas->where('status', $status)->count()]);

        return view('dashboard.index', [
            'filtros' => $filtros,
            'projetos' => $this->projetosVisiveis($usuario),
            'total' => $demandas->count(),
            'emTriagem' => $demandas->filter(fn (Solicitacao $demanda) => $demanda->status->permiteTriagem())->count(),
            'emExecucao' => $demandas->filter(fn (Solicitacao $demanda) => $demanda->status->permiteEntrega())->count(),
            'concluidas' => $demandas->where('status', StatusDemanda::Concluida)->count(),
            'tempoDeAtendimento' => $this->mediaEmDias($demandas, fn (Solicitacao $demanda) => $this->dataDoStatus($demanda, fn (Historico $historico) => ($historico->dados['para'] ?? null) === StatusDemanda::Concluida->value && $demanda->status === StatusDemanda::Concluida)),
            'tempoDePrimeiraResposta' => $this->mediaEmDias($demandas, fn (Solicitacao $demanda) => $this->dataDoStatus($demanda, fn (Historico $historico) => ($historico->dados['de'] ?? null) === StatusDemanda::Cadastrada->value, primeira: true)),
            'porStatus' => $porStatus,
            'porCategoria' => $this->contar($demandas, fn (Solicitacao $demanda) => $demanda->categoria?->nome ?? 'Sem categoria (não classificada)'),
            'porEscola' => $this->agruparOutras($this->contar($demandas, fn (Solicitacao $demanda) => $demanda->escolaInstituicao?->nome ?? 'Sem escola')),
            'porProjeto' => $this->contar($demandas, fn (Solicitacao $demanda) => $demanda->projetoBase?->titulo ?? 'Sem projeto'),
        ]);
    }

    /**
     * Data em que a demanda chegou a um status, pela linha do tempo (RF08). Por padrão, a última vez.
     *
     * @param  callable(Historico): bool  $criterio
     */
    private function dataDoStatus(Solicitacao $demanda, callable $criterio, bool $primeira = false): ?Carbon
    {
        $historicos = $demanda->historicos->filter($criterio);

        return ($primeira ? $historicos->first() : $historicos->last())?->created_at;
    }

    /**
     * Média, em dias, entre a abertura da demanda e a data informada. Demandas sem a data ficam de fora.
     *
     * @param  Collection<int, Solicitacao>  $demandas
     * @param  callable(Solicitacao): ?Carbon  $data
     * @return array{dias: ?float, demandas: int}
     */
    private function mediaEmDias(Collection $demandas, callable $data): array
    {
        $dias = $demandas
            ->map(fn (Solicitacao $demanda) => ($fim = $data($demanda)) ? $demanda->created_at->diffInMinutes($fim) / (60 * 24) : null)
            ->filter(fn (?float $dias) => $dias !== null);

        return ['dias' => $dias->isEmpty() ? null : round($dias->avg(), 1), 'demandas' => $dias->count()];
    }

    /**
     * @param  Collection<int, Solicitacao>  $demandas
     * @param  callable(Solicitacao): string  $rotulo
     * @return Collection<string, int>
     */
    private function contar(Collection $demandas, callable $rotulo): Collection
    {
        return $demandas->countBy($rotulo)->sortKeys()->sortDesc();
    }

    /**
     * Mantém as escolas com mais demandas e soma as demais em "Outras".
     *
     * @param  Collection<string, int>  $contagem
     * @return Collection<string, int>
     */
    private function agruparOutras(Collection $contagem): Collection
    {
        if ($contagem->count() <= self::ESCOLAS_NO_GRAFICO) {
            return $contagem;
        }

        $outras = $contagem->slice(self::ESCOLAS_NO_GRAFICO);

        return $contagem->take(self::ESCOLAS_NO_GRAFICO)->put("Outras ({$outras->count()} escolas)", $outras->sum());
    }

    /**
     * @return Collection<int, ProjetoBase>
     */
    private function projetosVisiveis(Usuario $usuario): Collection
    {
        return ProjetoBase::query()
            ->whereHas('solicitacoes', fn (Builder $query) => $query->visiveisPara($usuario))
            ->orderBy('titulo')
            ->get(['id', 'titulo']);
    }
}
