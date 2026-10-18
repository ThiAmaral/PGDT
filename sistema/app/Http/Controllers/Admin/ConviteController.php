<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convite;
use App\Notifications\ConviteCoordenador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Convites para o Coordenador projeto de extensão (sem autocadastro).
 */
class ConviteController extends Controller
{
    public function index(): View
    {
        return view('admin.convites.index', [
            'convites' => Convite::with('criador')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $dados = $request->validate([
            'email' => ['required', 'email', 'max:150', 'unique:usuarios,email'],
        ], [
            'email.unique' => 'Já existe uma conta com este e-mail. Para torná-la coordenador, troque o perfil em Usuários.',
        ]);

        // Um convite novo substitui o anterior ainda válido para o mesmo e-mail.
        Convite::query()->validos()->where('email', $dados['email'])->update(['expira_em' => now()]);

        [, $token] = Convite::gerar($dados['email'], $request->user());

        Notification::route('mail', $dados['email'])->notify(new ConviteCoordenador($token));

        return redirect()->route('admin.convites.index')->with('sucesso', "Convite enviado para {$dados['email']}.");
    }

    public function cancelar(Convite $convite): RedirectResponse
    {
        if ($convite->situacao() === 'Válido') {
            $convite->update(['expira_em' => now()]);
        }

        return redirect()->route('admin.convites.index')->with('sucesso', "Convite para {$convite->email} cancelado.");
    }
}
