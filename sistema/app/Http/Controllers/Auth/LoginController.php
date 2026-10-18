<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | Login único para todos os perfis (RF03): o perfil vem da conta.
    | Contas `Inativo` e `Rejeitado` não entram e recebem uma mensagem clara.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/painel';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Desfaz o login de contas bloqueadas logo depois de conferir a senha.
     *
     * @throws ValidationException
     */
    protected function authenticated(Request $request, Usuario $usuario): void
    {
        $mensagem = $usuario->mensagemBloqueio();

        if ($mensagem === null) {
            return;
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw ValidationException::withMessages(['email' => $mensagem]);
    }
}
