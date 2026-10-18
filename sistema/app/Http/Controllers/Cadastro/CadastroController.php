<?php

namespace App\Http\Controllers\Cadastro;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Enums\TipoEscola;
use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroDemandanteRequest;
use App\Http\Requests\CadastroEstudanteRequest;
use App\Models\Curso;
use App\Models\EscolaInstituicao;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Autocadastro separado por perfil (RF01 e RF02). Os dois nascem `Pendente`.
 */
class CadastroController extends Controller
{
    public function escolha(): View
    {
        return view('cadastro.escolha');
    }

    public function criarEstudante(): View
    {
        return view('cadastro.estudante', [
            'cursos' => Curso::query()->ativos()->get(),
        ]);
    }

    public function salvarEstudante(CadastroEstudanteRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $usuario = DB::transaction(function () use ($dados): Usuario {
            $usuario = Usuario::create([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'] ?? null,
                'password' => $dados['password'],
                'perfil' => Perfil::Estudante,
                'status' => StatusUsuario::Pendente,
            ]);

            $usuario->estudante()->create([
                'matricula' => $dados['matricula'],
                'curso_id' => $dados['curso_id'],
            ]);

            return $usuario;
        });

        // Dispara o e-mail de confirmação; o estudante só entra na fila do admin depois de confirmar.
        event(new Registered($usuario));
        Auth::login($usuario);

        return redirect()->route('verification.notice');
    }

    public function criarDemandante(): View
    {
        return view('cadastro.demandante', [
            'escolas' => EscolaInstituicao::query()->ativas()->get(),
            'tiposEscola' => TipoEscola::opcoes(),
        ]);
    }

    public function salvarDemandante(CadastroDemandanteRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $usuario = DB::transaction(function () use ($dados): Usuario {
            $escolaId = $dados['escola_modo'] === 'nova'
                ? EscolaInstituicao::create([
                    'nome' => $dados['nova_escola_nome'],
                    'tipo' => $dados['nova_escola_tipo'],
                    'cidade' => $dados['nova_escola_cidade'],
                    'uf' => $dados['nova_escola_uf'],
                    'ativo' => false,
                ])->id
                : $dados['escola_instituicao_id'];

            $usuario = Usuario::create([
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'password' => $dados['password'],
                'perfil' => Perfil::Demandante,
                'status' => StatusUsuario::Pendente,
                // A confirmação de e-mail foi decidida só para estudantes (Fase 0).
                'email_verified_at' => now(),
            ]);

            $usuario->demandante()->create([
                'escola_instituicao_id' => $escolaId,
                'cargo' => $dados['cargo'],
            ]);

            return $usuario;
        });

        event(new Registered($usuario));
        Auth::login($usuario);

        return redirect()->route('cadastro.em-analise');
    }

    public function emAnalise(): View|RedirectResponse
    {
        $usuario = Auth::user();

        if ($usuario->status !== StatusUsuario::Pendente) {
            return redirect()->route('home');
        }

        return view('cadastro.em-analise', ['usuario' => $usuario]);
    }
}
