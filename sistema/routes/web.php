<?php

use App\Http\Controllers\Admin\ConviteController as AdminConviteController;
use App\Http\Controllers\Admin\DominioController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Cadastro\CadastroController;
use App\Http\Controllers\Cadastro\ConviteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjetoBaseController;
use App\Http\Controllers\SolicitacaoController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('inicio');

// RF03: login único, logout, recuperação de senha e confirmação de e-mail.
Auth::routes(['register' => false, 'verify' => true]);

// RF01 e RF02: autocadastro separado por perfil; coordenador só por convite.
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [CadastroController::class, 'escolha'])->name('register');
    Route::get('/cadastro/estudante', [CadastroController::class, 'criarEstudante'])->name('cadastro.estudante');
    Route::post('/cadastro/estudante', [CadastroController::class, 'salvarEstudante']);
    Route::get('/cadastro/demandante', [CadastroController::class, 'criarDemandante'])->name('cadastro.demandante');
    Route::post('/cadastro/demandante', [CadastroController::class, 'salvarDemandante']);

    Route::get('/convite/{token}', [ConviteController::class, 'criar'])->name('convite.aceitar');
    Route::post('/convite/{token}', [ConviteController::class, 'salvar']);
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
});
