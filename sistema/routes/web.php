<?php

use App\Http\Controllers\Admin\ConviteController as AdminConviteController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Cadastro\CadastroController;
use App\Http\Controllers\Cadastro\ConviteController;
use App\Http\Controllers\HomeController;
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
    });
});
