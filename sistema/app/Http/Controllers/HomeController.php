<?php

namespace App\Http\Controllers;

use App\Enums\Perfil;
use App\Enums\SituacaoEntrega;
use App\Enums\StatusDemanda;
use App\Enums\StatusUsuario;
use App\Models\Entrega;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
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
            // RF11: candidaturas aguardando avaliação nos sub-projetos que o usuário gerencia.
            'totalCandidaturasPendentes' => $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador)
                ? Subprojeto::candidaturasPendentesPara($usuario)
                : 0,
            // RF13: entregas aguardando a validação técnica (admin e coordenador) ou o aceite (demandante).
            'totalEntregasParaValidar' => $usuario->temPerfil(Perfil::Admin, Perfil::Coordenador)
                ? Entrega::query()
                    ->where('situacao', SituacaoEntrega::AguardandoTecnica)
                    ->whereHas('subprojeto', fn ($query) => $query->visiveisPara($usuario))
                    ->count()
                : 0,
            'totalEntregasParaAceitar' => $usuario->temPerfil(Perfil::Demandante)
                ? Entrega::query()
                    ->where('situacao', SituacaoEntrega::AguardandoAceite)
                    ->whereHas('subprojeto.solicitacao', fn ($query) => $query->where('demandante_id', $usuario->id))
                    ->count()
                : 0,
            // RF10: vagas abertas e equipes do estudante.
            'totalOportunidades' => $usuario->temPerfil(Perfil::Estudante)
                ? Subprojeto::query()->comVagasAbertas()->count()
                : 0,
            'minhasEquipes' => $usuario->temPerfil(Perfil::Estudante)
                ? Subprojeto::query()->with('solicitacao')->visiveisPara($usuario)->latest()->limit(5)->get()
                : collect(),
            'minhasDemandas' => $usuario->temPerfil(Perfil::Demandante)
                ? Solicitacao::query()->visiveisPara($usuario)->latest()->orderByDesc('id')->limit(5)->get()
                : collect(),
        ]);
    }
}
