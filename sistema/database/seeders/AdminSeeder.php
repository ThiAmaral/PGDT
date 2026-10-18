<?php

namespace Database\Seeders;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Cria o administrador inicial, se ainda não existir.
     */
    public function run(): void
    {
        $dados = config('pgdt.admin');

        if (Usuario::where('email', $dados['email'])->exists()) {
            $this->command?->info("Administrador {$dados['email']} já existe.");

            return;
        }

        $senha = $dados['senha'] ?: Str::password(16);

        Usuario::create([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'password' => $senha,
            'perfil' => Perfil::Admin,
            'status' => StatusUsuario::Ativo,
            'email_verified_at' => now(),
            'aprovado_em' => now(),
        ]);

        $this->command?->info("Administrador criado: {$dados['email']}");

        if (! $dados['senha']) {
            $this->command?->warn("Senha gerada (anote e troque depois): {$senha}");
        }
    }
}
