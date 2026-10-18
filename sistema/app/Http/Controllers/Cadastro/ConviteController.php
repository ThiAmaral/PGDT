<?php

namespace App\Http\Controllers\Cadastro;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Http\Controllers\Controller;
use App\Models\Convite;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Aceite do convite do Coordenador projeto de extensão: link de uso único e com prazo.
 */
class ConviteController extends Controller
{
    public function criar(string $token): View
    {
        $convite = Convite::buscarValidoPorToken($token);

        if (! $convite || Usuario::where('email', $convite->email)->exists()) {
            return view('convite.invalido');
        }

        return view('convite.aceitar', ['convite' => $convite, 'token' => $token]);
    }

    public function salvar(Request $request, string $token): View|RedirectResponse
    {
        $convite = Convite::buscarValidoPorToken($token);

        if (! $convite || Usuario::where('email', $convite->email)->exists()) {
            return view('convite.invalido');
        }

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:150'],
            'telefone' => ['nullable', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'siape' => ['nullable', 'digits_between:5,20', 'unique:coordenadores,siape'],
            'departamento' => ['nullable', 'string', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'telefone.regex' => 'Informe o telefone no formato (28) 99999-9999.',
            'siape.unique' => 'Este SIAPE já está cadastrado.',
        ], [
            'password' => 'senha',
        ]);

        $usuario = DB::transaction(function () use ($convite, $dados): Usuario {
            $usuario = Usuario::create([
                'nome' => $dados['nome'],
                'email' => $convite->email,
                'telefone' => $dados['telefone'] ?? null,
                'password' => $dados['password'],
                'perfil' => Perfil::Coordenador,
                'status' => StatusUsuario::Ativo,
                // O link chegou no e-mail convidado, então o e-mail já está confirmado.
                'email_verified_at' => now(),
                'aprovado_por' => $convite->criado_por,
                'aprovado_em' => now(),
            ]);

            $usuario->coordenador()->create([
                'siape' => $dados['siape'] ?? null,
                'departamento' => $dados['departamento'] ?? null,
            ]);

            $convite->update(['usado_em' => now()]);

            return $usuario;
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('home')->with('sucesso', 'Cadastro concluído. Bem-vindo(a)!');
    }
}
