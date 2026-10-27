<?php

namespace App\Http\Controllers;

use App\Enums\Modalidade;
use App\Enums\Perfil;
use App\Enums\SituacaoEntrega;
use App\Enums\StatusCandidatura;
use App\Models\Candidatura;
use App\Models\PapelEquipe;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RF09: conversão da demanda em sub-projeto do projeto de extensão. RF11: tela da equipe e das candidaturas.
 */
class SubprojetoController extends Controller
{
    /** Limites do formulário. */
    public const CARGA_HORARIA_MAXIMA = 2000;

    public const VAGAS_MAXIMAS = 50;

    /**
     * Coordenador e admin: sub-projetos que gerenciam. Estudante: as suas equipes e candidaturas.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Subprojeto::class);

        $usuario = $request->user();

        $subprojetos = Subprojeto::query()
            ->with('solicitacao.projetoBase')
            ->withCount([
                'membrosAtivos',
                'candidaturas as candidaturas_pendentes_count' => fn ($query) => $query->where('status', StatusCandidatura::Pendente),
                'entregas as entregas_para_validar_count' => fn ($query) => $query->where('situacao', SituacaoEntrega::AguardandoTecnica),
            ])
            ->visiveisPara($usuario)
            ->latest()
            ->orderByDesc('id')
            ->paginate(15);

        return view('subprojetos.index', [
            'subprojetos' => $subprojetos,
            'ehEstudante' => $usuario->temPerfil(Perfil::Estudante),
            'minhasCandidaturas' => $usuario->temPerfil(Perfil::Estudante)
                ? Candidatura::query()
                    ->with('subprojeto.solicitacao.projetoBase')
                    ->where('estudante_id', $usuario->id)
                    ->latest()
                    ->orderByDesc('id')
                    ->get()
                : collect(),
        ]);
    }

    /**
     * Formulário pré-preenchido com os dados da demanda.
     */
    public function create(Solicitacao $solicitacao): View
    {
        Gate::authorize('create', [Subprojeto::class, $solicitacao]);

        $subprojeto = new Subprojeto([
            'titulo' => $solicitacao->titulo,
            'escopo' => $solicitacao->descricao,
            'data_inicio' => now(),
            'vagas' => 1,
            'candidaturas_abertas' => true,
        ]);

        return view('subprojetos.form', [
            'subprojeto' => $subprojeto,
            'solicitacao' => $solicitacao,
            'modalidades' => Modalidade::cases(),
            'editaDados' => true,
        ]);
    }

    public function store(Request $request, Solicitacao $solicitacao): RedirectResponse
    {
        Gate::authorize('create', [Subprojeto::class, $solicitacao]);

        $dados = $this->validar($request);

        // O coordenador logado vira o responsável. Se for o admin, fica o coordenador do projeto (quando houver).
        $usuario = $request->user();
        $responsavel = $usuario->temPerfil(Perfil::Coordenador)
            ? $usuario->id
            : ($solicitacao->projetoBase->coordenador_id ?? $usuario->id);

        $solicitacao->subprojeto()->create([
            ...$dados,
            'coordenador_id' => $responsavel,
            'candidaturas_abertas' => true,
        ]);

        return redirect()->route('demandas.show', $solicitacao)
            ->with('sucesso', 'Sub-projeto criado e aberto para candidaturas na vitrine. Se a equipe já pode começar, mude a demanda para "Em Desenvolvimento".');
    }

    public function show(Request $request, Subprojeto $subprojeto): View
    {
        Gate::authorize('view', $subprojeto);

        $subprojeto->load([
            'solicitacao.projetoBase',
            'coordenador',
            'membros' => fn ($query) => $query->orderByRaw('removido_em is not null')->orderBy('created_at'),
            'membros.estudante.estudante.curso',
            'membros.papel',
            'candidaturas' => fn ($query) => $query->orderBy('created_at'),
            'candidaturas.estudante.estudante.curso',
            'candidaturas.avaliador',
        ])->loadCount('membrosAtivos');

        $papeis = PapelEquipe::query()->ativos()->get();
        $usuario = $request->user();

        // RF12: o demandante só vê as entregas que já passaram pela aprovação técnica.
        $entregas = $subprojeto->entregas()
            ->with('remetente')
            ->when(
                $subprojeto->solicitacao->demandante_id === $usuario->id && ! Gate::allows('gerencia', $subprojeto->solicitacao),
                fn ($query) => $query->visiveisAoDemandante(),
            )
            ->get();

        return view('subprojetos.show', [
            'subprojeto' => $subprojeto,
            'entregas' => $entregas,
            'ehMembro' => $usuario->temPerfil(Perfil::Estudante) && $subprojeto->ehMembroAtivo($usuario),
            // Membros da equipe e, em nome dela, quem gerencia a demanda (decisão (b) da Fase 6).
            'podeEnviarEntrega' => ($usuario->temPerfil(Perfil::Estudante) && $subprojeto->ehMembroAtivo($usuario))
                || Gate::allows('gerencia', $subprojeto->solicitacao),
            'impedimentoDeEntrega' => $subprojeto->impedimentoDeEntrega($usuario),
            'papeis' => $papeis,
            'papelPadrao' => $papeis->firstWhere('nome', PapelEquipe::PADRAO) ?? $papeis->first(),
            'gerencia' => Gate::allows('gerenciarEquipe', $subprojeto),
        ]);
    }

    public function edit(Subprojeto $subprojeto): View
    {
        Gate::authorize('alterarCargaHoraria', $subprojeto);

        return view('subprojetos.form', [
            'subprojeto' => $subprojeto,
            'solicitacao' => $subprojeto->solicitacao,
            'modalidades' => Modalidade::cases(),
            'editaDados' => Gate::allows('update', $subprojeto),
        ]);
    }

    /**
     * Com a demanda encerrada, só a carga horária estimada pode ser corrigida (RF15).
     */
    public function update(Request $request, Subprojeto $subprojeto): RedirectResponse
    {
        Gate::authorize('alterarCargaHoraria', $subprojeto);

        if (Gate::allows('update', $subprojeto)) {
            $dados = $this->validar($request, $subprojeto);
            $dados['candidaturas_abertas'] = $request->boolean('candidaturas_abertas');
        } else {
            $dados = $request->validate([
                'carga_horaria_estimada' => ['required', 'integer', 'min:1', 'max:'.self::CARGA_HORARIA_MAXIMA],
            ], [], ['carga_horaria_estimada' => 'carga horária estimada']);
        }

        // updateOrFail: a alteração e o registro no histórico são gravados juntos ou nenhum dos dois.
        $subprojeto->updateOrFail($dados);

        return redirect()->route('subprojetos.show', $subprojeto)->with('sucesso', 'Sub-projeto atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Subprojeto $subprojeto = null): array
    {
        // As vagas não podem ficar abaixo do número de membros que já estão na equipe.
        $minimoDeVagas = max(1, $subprojeto?->membrosAtivos()->count() ?? 0);

        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'escopo' => ['required', 'string', 'max:5000'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'carga_horaria_estimada' => ['required', 'integer', 'min:1', 'max:'.self::CARGA_HORARIA_MAXIMA],
            'vagas' => ['required', 'integer', 'min:'.$minimoDeVagas, 'max:'.self::VAGAS_MAXIMAS],
            'modalidade' => ['required', Rule::enum(Modalidade::class)],
        ], [
            'data_fim.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'vagas.min' => $minimoDeVagas > 1
                ? "A equipe já tem {$minimoDeVagas} membros: as vagas não podem ser menos que isso."
                : 'Informe pelo menos 1 vaga.',
        ], [
            'titulo' => 'título',
            'data_inicio' => 'data de início',
            'data_fim' => 'data de término',
            'carga_horaria_estimada' => 'carga horária estimada',
        ]);
    }
}
