<?php

use App\Http\Controllers\Admin\ConviteController as AdminConviteController;
use App\Http\Controllers\Admin\DominioController;
use App\Http\Controllers\Admin\ParametroController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Cadastro\CadastroController;
use App\Http\Controllers\Cadastro\ConviteController;
use App\Http\Controllers\CandidaturaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MembroEquipeController;
use App\Http\Controllers\MensagemController;
use App\Http\Controllers\OportunidadeController;
use App\Http\Controllers\ProjetoBaseController;
use App\Http\Controllers\ProjetoPublicoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\SolicitacaoController;
use App\Http\Controllers\SubprojetoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('inicio');

// Fase 8 (LGPD): aviso de privacidade, sem login.
Route::view('/privacidade', 'privacidade')->name('privacidade');

// RF14: consulta pública dos projetos de extensão, sem login.
Route::get('/projetos', [ProjetoPublicoController::class, 'index'])->name('publico.index');
Route::get('/projetos/{projetoBase}', [ProjetoPublicoController::class, 'show'])->whereNumber('projetoBase')->name('publico.show');

// RF03: login único, logout, recuperação de senha e confirmação de e-mail.
Auth::routes(['register' => false, 'verify' => true]);

// RF01 e RF02: autocadastro separado por perfil; coordenador só por convite.
// Até 10 envios por minuto, para conter cadastros em massa (Fase 8).
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [CadastroController::class, 'escolha'])->name('register');
    Route::get('/cadastro/estudante', [CadastroController::class, 'criarEstudante'])->name('cadastro.estudante');
    Route::post('/cadastro/estudante', [CadastroController::class, 'salvarEstudante'])->middleware('throttle:10,1');
    Route::get('/cadastro/demandante', [CadastroController::class, 'criarDemandante'])->name('cadastro.demandante');
    Route::post('/cadastro/demandante', [CadastroController::class, 'salvarDemandante'])->middleware('throttle:10,1');

    Route::get('/convite/{token}', [ConviteController::class, 'criar'])->name('convite.aceitar');
    Route::post('/convite/{token}', [ConviteController::class, 'salvar'])->middleware('throttle:10,1');
});

Route::redirect('/register', '/cadastro');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cadastro-em-analise', [CadastroController::class, 'emAnalise'])->name('cadastro.em-analise');
});

Route::middleware(['auth', 'verified', 'ativo'])->group(function () {
    Route::get('/painel', [HomeController::class, 'index'])->name('home');
    Route::redirect('/home', '/painel');

    // RF04: administração de usuários.
    Route::middleware('perfil:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('usuarios.show');
        Route::patch('/usuarios/{usuario}/status', [UsuarioController::class, 'atualizarStatus'])->name('usuarios.status');
        Route::patch('/usuarios/{usuario}/perfil', [UsuarioController::class, 'atualizarPerfil'])->name('usuarios.perfil');
        Route::patch('/usuarios/{usuario}/escola', [UsuarioController::class, 'atualizarEscola'])->name('usuarios.escola');

        Route::get('/convites', [AdminConviteController::class, 'index'])->name('convites.index');
        Route::post('/convites', [AdminConviteController::class, 'store'])->name('convites.store');
        Route::patch('/convites/{convite}/cancelar', [AdminConviteController::class, 'cancelar'])->name('convites.cancelar');

        // Cadastros de apoio: tipologias, categorias de demanda e prioridades.
        Route::get('/cadastros/{tipo}', [DominioController::class, 'index'])->whereIn('tipo', array_keys(DominioController::TIPOS))->name('dominios.index');
        Route::post('/cadastros/{tipo}', [DominioController::class, 'store'])->whereIn('tipo', array_keys(DominioController::TIPOS))->name('dominios.store');
        Route::patch('/cadastros/{tipo}/{id}', [DominioController::class, 'update'])->whereIn('tipo', array_keys(DominioController::TIPOS))->whereNumber('id')->name('dominios.update');
        Route::redirect('/cadastros', '/admin/cadastros/tipologias');

        // Fase 7: parâmetros gerais do sistema.
        Route::get('/parametros', [ParametroController::class, 'index'])->name('parametros.index');
        Route::patch('/parametros', [ParametroController::class, 'update'])->name('parametros.update');
    });

    // RF05: Projetos Base. As permissões ficam na ProjetoBasePolicy.
    Route::resource('projetos-base', ProjetoBaseController::class)
        ->parameters(['projetos-base' => 'projetoBase'])
        ->except('destroy');

    // RF06 e RF07: demandas. As permissões ficam na SolicitacaoPolicy.
    Route::resource('demandas', SolicitacaoController::class)
        ->parameters(['demandas' => 'solicitacao'])
        ->only(['index', 'create', 'store', 'show']);
    Route::patch('/demandas/{solicitacao}/triagem', [SolicitacaoController::class, 'triar'])->name('demandas.triagem');
    Route::patch('/demandas/{solicitacao}/status', [SolicitacaoController::class, 'alterarStatus'])->name('demandas.status');
    Route::get('/demandas/{solicitacao}/anexos/{anexo}', [SolicitacaoController::class, 'baixarAnexo'])
        ->scopeBindings()
        ->name('demandas.anexo');

    // RF08: chat da demanda. A linha do tempo é gravada pelo SolicitacaoObserver.
    Route::post('/demandas/{solicitacao}/mensagens', [MensagemController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('demandas.mensagens.store');

    // RF09: sub-projetos. As permissões ficam na SubprojetoPolicy.
    Route::get('/demandas/{solicitacao}/subprojeto/criar', [SubprojetoController::class, 'create'])->name('subprojetos.create');
    Route::post('/demandas/{solicitacao}/subprojeto', [SubprojetoController::class, 'store'])->name('subprojetos.store');
    Route::resource('subprojetos', SubprojetoController::class)->only(['index', 'show', 'edit', 'update']);

    // RF10: vitrine de oportunidades e candidatura.
    Route::get('/oportunidades', [OportunidadeController::class, 'index'])->name('oportunidades.index');
    Route::get('/oportunidades/{subprojeto}', [OportunidadeController::class, 'show'])->name('oportunidades.show');
    Route::post('/oportunidades/{subprojeto}/candidatura', [CandidaturaController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('candidaturas.store');

    // RF11: avaliação das candidaturas e gestão da equipe.
    Route::scopeBindings()->prefix('/subprojetos/{subprojeto}')->name('subprojetos.')->group(function () {
        Route::patch('/candidaturas/{candidatura}/cancelar', [CandidaturaController::class, 'cancelar'])->name('candidaturas.cancelar');
        Route::patch('/candidaturas/{candidatura}/aprovar', [CandidaturaController::class, 'aprovar'])->name('candidaturas.aprovar');
        Route::patch('/candidaturas/{candidatura}/recusar', [CandidaturaController::class, 'recusar'])->name('candidaturas.recusar');
        Route::patch('/membros/{membro}/papel', [MembroEquipeController::class, 'alterarPapel'])->name('membros.papel');
        Route::patch('/membros/{membro}/remover', [MembroEquipeController::class, 'remover'])->name('membros.remover');

        // RF12 e RF13: entregas da equipe e validação em duas etapas. As permissões ficam na EntregaPolicy.
        Route::get('/entregas/criar', [EntregaController::class, 'create'])->name('entregas.create');
        Route::post('/entregas', [EntregaController::class, 'store'])->middleware('throttle:10,1')->name('entregas.store');
        Route::get('/entregas/{entrega}', [EntregaController::class, 'show'])->name('entregas.show');
        Route::get('/entregas/{entrega}/arquivos/{arquivo}', [EntregaController::class, 'baixarArquivo'])->name('entregas.arquivo');
        Route::post('/entregas/{entrega}/validacao', [EntregaController::class, 'validar'])->name('entregas.validar');
        Route::post('/entregas/{entrega}/aceite', [EntregaController::class, 'aceitar'])->name('entregas.aceitar');
    });

    // Fase 7: dashboard gerencial.
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('perfil:admin,coordenador')->name('dashboard');

    // RF15: relatório de demandas executadas por extensionista.
    Route::get('/relatorios/extensionistas', [RelatorioController::class, 'extensionistas'])->name('relatorios.extensionistas');
    Route::get('/relatorios/extensionistas/pdf', [RelatorioController::class, 'extensionistasPdf'])->name('relatorios.extensionistas.pdf');
});
