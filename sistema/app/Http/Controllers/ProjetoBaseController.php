<?php

namespace App\Http\Controllers;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\ProjetoBase;
use App\Models\Tipologia;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RF05: cadastro e listagem de Projetos Base (projetos de extensão).
 */
class ProjetoBaseController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $gerencia = Gate::allows('create', ProjetoBase::class);

        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:150'],
            'tipologia' => ['nullable', 'integer'],
            'situacao' => ['nullable', Rule::in(['ativos', 'inativos'])],
            'meus' => ['nullable', 'boolean'],
        ]);

        // Quem não gerencia projetos vê só os ativos.
        if (! $gerencia) {
            $filtros['situacao'] = 'ativos';
            $filtros['meus'] = null;
        }

        $projetos = ProjetoBase::query()
            ->with(['tipologia', 'coordenador'])
            ->filtrar($filtros, $usuario)
            ->orderByDesc('ativo')
            ->orderBy('titulo')
            ->paginate(15)
            ->withQueryString();

        return view('projetos-base.index', [
            'projetos' => $projetos,
            'filtros' => $filtros,
            'tipologias' => Tipologia::query()->ordenados()->get(),
            'gerencia' => $gerencia,
            'semCoordenador' => $usuario->temPerfil(Perfil::Admin)
                ? ProjetoBase::whereNull('coordenador_id')->count()
                : 0,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ProjetoBase::class);

        $projeto = new ProjetoBase([
            'data_inicio' => now(),
            'publico' => true,
            'ativo' => true,
            'coordenador_id' => $request->user()->temPerfil(Perfil::Coordenador) ? $request->user()->id : null,
        ]);

        return view('projetos-base.form', $this->dadosDoFormulario($projeto, $request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ProjetoBase::class);

        $usuario = $request->user();
        $dados = $this->validar($request, $usuario->temPerfil(Perfil::Admin));

        // O coordenador que cadastra vira o responsável; só o admin escolhe outro.
        if (! $usuario->temPerfil(Perfil::Admin)) {
            $dados['coordenador_id'] = $usuario->id;
        }

        $projeto = ProjetoBase::create($dados);

        return redirect()->route('projetos-base.show', $projeto)->with('sucesso', 'Projeto Base cadastrado.');
    }

    public function show(ProjetoBase $projetoBase): View
    {
        Gate::authorize('view', $projetoBase);

        $projetoBase->load(['tipologia', 'coordenador.coordenador']);

        return view('projetos-base.show', ['projeto' => $projetoBase]);
    }

    public function edit(Request $request, ProjetoBase $projetoBase): View
    {
        Gate::authorize('update', $projetoBase);

        return view('projetos-base.form', $this->dadosDoFormulario($projetoBase, $request->user()));
    }

    public function update(Request $request, ProjetoBase $projetoBase): RedirectResponse
    {
        Gate::authorize('update', $projetoBase);

        $podeTrocarCoordenador = Gate::allows('trocarCoordenador', $projetoBase);
        $dados = $this->validar($request, $podeTrocarCoordenador, $projetoBase->tipologia_id);

        if (! $podeTrocarCoordenador) {
            unset($dados['coordenador_id']);
        }

        $projetoBase->update($dados);

        return redirect()->route('projetos-base.show', $projetoBase)->with('sucesso', 'Projeto Base atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, bool $escolheCoordenador, ?int $tipologiaAtual = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['required', 'string', 'max:5000'],
            'escopo_preliminar' => ['nullable', 'string', 'max:5000'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            // A tipologia atual continua válida mesmo que o admin a tenha inativado depois.
            'tipologia_id' => ['required', Rule::exists('tipologias', 'id')->where(
                fn ($query) => $query->where('ativo', true)->orWhere('id', $tipologiaAtual)
            )],
            'coordenador_id' => $escolheCoordenador
                ? ['required', Rule::exists('usuarios', 'id')->where('perfil', Perfil::Coordenador->value)->where('status', StatusUsuario::Ativo->value)]
                : ['prohibited'],
            'publico' => ['nullable', 'boolean'],
            'ativo' => ['nullable', 'boolean'],
        ], [
            'data_fim.after_or_equal' => 'A data de término não pode ser anterior à data de início.',
            'coordenador_id.exists' => 'Escolha um Coordenador projeto de extensão ativo.',
        ], [
            'titulo' => 'título',
            'descricao' => 'descrição',
            'escopo_preliminar' => 'escopo preliminar',
            'data_inicio' => 'data de início',
            'data_fim' => 'data de término',
            'tipologia_id' => 'tipologia',
            'coordenador_id' => 'coordenador responsável',
        ]);

        // Checkbox desmarcado não é enviado pelo navegador.
        $dados['publico'] = $request->boolean('publico');
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosDoFormulario(ProjetoBase $projeto, Usuario $usuario): array
    {
        $tipologias = Tipologia::query()->ativos()->get();

        // Mantém na lista a tipologia atual, mesmo que o admin a tenha inativado depois.
        if ($projeto->tipologia && ! $projeto->tipologia->ativo) {
            $tipologias->push($projeto->tipologia);
        }

        return [
            'projeto' => $projeto,
            'tipologias' => $tipologias,
            'escolheCoordenador' => $usuario->temPerfil(Perfil::Admin),
            'coordenadores' => Usuario::query()
                ->where('perfil', Perfil::Coordenador)
                ->where('status', StatusUsuario::Ativo)
                ->orderBy('nome')
                ->get(),
        ];
    }
}
