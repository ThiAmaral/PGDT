<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Enums\TipoEscola;
use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\EscolaInstituicao;
use App\Models\Usuario;
use App\Notifications\CadastroAprovado;
use App\Notifications\CadastroRejeitado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * RF04: o admin aprova os usuários, define o status e troca o perfil.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:150'],
            'perfil' => ['nullable', Rule::enum(Perfil::class)],
            'status' => ['nullable', Rule::enum(StatusUsuario::class)],
        ]);

        $usuarios = Usuario::query()
            ->filtrar($filtros)
            ->orderByRaw('status = ? desc', [StatusUsuario::Pendente->value])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'filtros' => $filtros,
            'perfis' => Perfil::opcoes(),
            'statusOpcoes' => StatusUsuario::opcoes(),
            'totalPendentes' => Usuario::where('status', StatusUsuario::Pendente)->whereNotNull('email_verified_at')->count(),
        ]);
    }

    public function show(Usuario $usuario): View
    {
        $usuario->load(['estudante.curso', 'demandante.escolaInstituicao', 'coordenador', 'aprovador']);

        return view('admin.usuarios.show', [
            'usuario' => $usuario,
            'perfis' => Perfil::opcoes(),
            'statusOpcoes' => StatusUsuario::opcoes(),
            'cursos' => Curso::query()->ativos()->get(),
            'escolas' => EscolaInstituicao::query()->ativas()->get(),
            'tiposEscola' => TipoEscola::opcoes(),
            'proprioUsuario' => $usuario->is(auth()->user()),
        ]);
    }

    public function atualizarStatus(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->impedirAlteracaoPropria($usuario);

        $dados = $request->validate([
            'status' => ['required', Rule::enum(StatusUsuario::class)],
            'motivo_status' => ['nullable', 'string', 'max:1000'],
        ]);

        $novoStatus = StatusUsuario::from($dados['status']);
        $motivo = trim((string) ($dados['motivo_status'] ?? ''));

        if ($novoStatus->exigeMotivo() && $motivo === '') {
            $acao = $novoStatus === StatusUsuario::Rejeitado ? 'rejeitar' : 'inativar';

            return back()->withErrors(['motivo_status' => "Informe o motivo para {$acao} o usuário."])->withInput();
        }

        if ($novoStatus === StatusUsuario::Ativo && ! $usuario->hasVerifiedEmail()) {
            return back()->withErrors(['status' => 'Este usuário ainda não confirmou o e-mail e não pode ser aprovado.']);
        }

        $statusAnterior = $usuario->status;
        $primeiraAprovacao = $novoStatus === StatusUsuario::Ativo && $usuario->aprovado_em === null;

        DB::transaction(function () use ($usuario, $novoStatus, $motivo, $primeiraAprovacao): void {
            $usuario->status = $novoStatus;
            $usuario->motivo_status = $novoStatus->exigeMotivo() ? $motivo : null;

            if ($primeiraAprovacao) {
                $usuario->aprovado_por = auth()->id();
                $usuario->aprovado_em = now();

                // A escola digitada no cadastro é conferida junto com a aprovação do demandante.
                $usuario->demandante?->escolaInstituicao()->where('ativo', false)->update(['ativo' => true]);
            }

            $usuario->save();
        });

        if ($primeiraAprovacao) {
            $usuario->notify(new CadastroAprovado);
        } elseif ($novoStatus === StatusUsuario::Rejeitado && $statusAnterior === StatusUsuario::Pendente) {
            $usuario->notify(new CadastroRejeitado($motivo));
        }

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('sucesso', "Status alterado de {$statusAnterior->rotulo()} para {$novoStatus->rotulo()}.");
    }

    public function atualizarPerfil(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->impedirAlteracaoPropria($usuario);

        $request->validate(['perfil' => ['required', Rule::enum(Perfil::class)]]);
        $novoPerfil = Perfil::from($request->input('perfil'));

        if ($novoPerfil === $usuario->perfil) {
            return back()->withErrors(['perfil' => 'O usuário já tem este perfil.']);
        }

        $dados = $request->validate($this->regrasDoPerfil($novoPerfil, $usuario), [
            'telefone.regex' => 'Informe o telefone no formato (28) 99999-9999.',
        ], [
            'curso_id' => 'curso',
            'escola_instituicao_id' => 'escola ou instituição',
        ]);

        $perfilAnterior = $usuario->perfil;

        DB::transaction(function () use ($usuario, $novoPerfil, $dados): void {
            // Os dados do perfil anterior ficam guardados; só passam a valer os do perfil novo.
            match ($novoPerfil) {
                Perfil::Estudante => $usuario->estudante()->updateOrCreate([], [
                    'matricula' => $dados['matricula'],
                    'curso_id' => $dados['curso_id'],
                ]),
                Perfil::Demandante => $usuario->demandante()->updateOrCreate([], [
                    'escola_instituicao_id' => $dados['escola_instituicao_id'],
                    'cargo' => $dados['cargo'],
                ]),
                Perfil::Coordenador => $usuario->coordenador()->updateOrCreate([], [
                    'siape' => $dados['siape'] ?? null,
                    'departamento' => $dados['departamento'] ?? null,
                ]),
                Perfil::Admin => null,
            };

            if (isset($dados['telefone'])) {
                $usuario->telefone = $dados['telefone'];
            }

            $usuario->perfil = $novoPerfil;
            $usuario->save();
        });

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('sucesso', "Perfil alterado de {$perfilAnterior->rotulo()} para {$novoPerfil->rotulo()}.");
    }

    public function atualizarEscola(Request $request, Usuario $usuario): RedirectResponse
    {
        $escola = $usuario->demandante?->escolaInstituicao;
        abort_unless($escola, 404);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::enum(TipoEscola::class)],
            'codigo_inep' => ['nullable', 'digits:8', Rule::unique('escolas_instituicoes', 'codigo_inep')->ignore($escola->id)],
            'cidade' => ['required', 'string', 'max:100'],
            'uf' => ['required', 'string', 'size:2', 'alpha'],
        ], [], ['codigo_inep' => 'código INEP']);

        $dados['uf'] = mb_strtoupper($dados['uf']);
        $escola->update($dados);

        return redirect()->route('admin.usuarios.show', $usuario)->with('sucesso', 'Dados da escola/instituição atualizados.');
    }

    /**
     * O admin não altera o próprio status ou perfil, para não perder o acesso ao painel.
     */
    private function impedirAlteracaoPropria(Usuario $usuario): void
    {
        abort_if($usuario->is(auth()->user()), 403, 'Você não pode alterar o seu próprio status ou perfil.');
    }

    /**
     * Campos obrigatórios para o usuário passar a ter o perfil novo.
     *
     * @return array<string, array<mixed>>
     */
    private function regrasDoPerfil(Perfil $perfil, Usuario $usuario): array
    {
        return match ($perfil) {
            Perfil::Estudante => [
                'matricula' => ['required', 'digits_between:6,20', Rule::unique('estudantes', 'matricula')->ignore($usuario->id, 'usuario_id')],
                'curso_id' => ['required', Rule::exists('cursos', 'id')->where('ativo', true)],
            ],
            Perfil::Demandante => [
                'escola_instituicao_id' => ['required', Rule::exists('escolas_instituicoes', 'id')->where('ativo', true)],
                'cargo' => ['required', 'string', 'max:100'],
                'telefone' => ['required', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            ],
            Perfil::Coordenador => [
                'siape' => ['nullable', 'digits_between:5,20', Rule::unique('coordenadores', 'siape')->ignore($usuario->id, 'usuario_id')],
                'departamento' => ['nullable', 'string', 'max:150'],
            ],
            Perfil::Admin => [],
        };
    }
}
