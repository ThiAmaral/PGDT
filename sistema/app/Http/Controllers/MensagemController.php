<?php

namespace App\Http\Controllers;

use App\Models\Mensagem;
use App\Models\Solicitacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * RF08: chat da demanda entre o demandante, a coordenação do projeto e o admin.
 */
class MensagemController extends Controller
{
    public function store(Request $request, Solicitacao $solicitacao): RedirectResponse
    {
        Gate::authorize('comentar', $solicitacao);

        $dados = $request->validate([
            'texto' => ['required', 'string', 'max:'.Mensagem::TAMANHO_MAXIMO],
        ], [
            'texto.required' => 'Escreva o comentário antes de enviar.',
        ], [
            'texto' => 'comentário',
        ]);

        $usuario = $request->user();

        $solicitacao->mensagens()->create([
            'usuario_id' => $usuario->id,
            'perfil' => $usuario->perfil,
            'texto' => $dados['texto'],
        ]);

        return redirect()->to(route('demandas.show', $solicitacao).'#chat')->with('sucesso', 'Comentário enviado.');
    }
}
