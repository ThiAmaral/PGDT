<?php

namespace App\Http\Middleware;

use App\Enums\StatusUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Só usuários `Ativo` usam o sistema. `Pendente` vê a tela "Cadastro em análise";
 * `Inativo` e `Rejeitado` são desconectados com a explicação.
 */
class ContaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario->status === StatusUsuario::Pendente) {
            return redirect()->route('cadastro.em-analise');
        }

        if (! $usuario->estaAtivo()) {
            $mensagem = $usuario->mensagemBloqueio();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $mensagem]);
        }

        return $next($request);
    }
}
