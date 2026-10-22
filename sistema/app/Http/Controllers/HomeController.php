<?php

namespace App\Http\Controllers;

use App\Enums\Perfil;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Painel inicial; o conteúdo muda conforme o perfil da conta.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();

        return view('home', [
            'usuario' => $usuario,
            'totalPendentes' => $usuario->temPerfil(Perfil::Admin)
                ? Usuario::where('status', StatusUsuario::Pendente)->whereNotNull('email_verified_at')->count()
                : 0,
            'totalMeusProjetos' => $usuario->temPerfil(Perfil::Coordenador)
                ? ProjetoBase::where('coordenador_id', $usuario->id)->count()
                : 0,
            // Demandas novas aguardando análise (admin e coordenador) ou abertas pelo demandante.
            'totalDemandasNovas' => $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador)
                ? Solicitacao::query()->visiveisPara($usuario)->where('status', StatusDemanda::Cadastrada)->count()
                : 0,
            'minhasDemandas' => $usuario->temPerfil(Perfil::Demandante)
                ? Solicitacao::query()->visiveisPara($usuario)->latest()->orderByDesc('id')->limit(5)->get()
                : collect(),
        ]);
    }
}
