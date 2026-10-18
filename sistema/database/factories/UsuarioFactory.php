<?php

namespace Database\Factories;

use App\Enums\Perfil;
use App\Enums\StatusUsuario;
use App\Models\Coordenador;
use App\Models\Curso;
use App\Models\Demandante;
use App\Models\EscolaInstituicao;
use App\Models\Estudante;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'perfil' => Perfil::Admin,
            'status' => StatusUsuario::Ativo,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function pendente(): static
    {
        return $this->comStatus(StatusUsuario::Pendente);
    }

    public function comStatus(StatusUsuario $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['perfil' => Perfil::Admin]);
    }

    public function estudante(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => Perfil::Estudante,
            'email' => fake()->unique()->userName().'@edu.ufes.br',
        ])->afterCreating(function (Usuario $usuario): void {
            Estudante::create([
                'usuario_id' => $usuario->id,
                'matricula' => fake()->unique()->numerify('20########'),
                'curso_id' => Curso::factory()->create()->id,
            ]);
        });
    }

    public function demandante(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => Perfil::Demandante,
            'telefone' => '(28) 99999-0000',
        ])->afterCreating(function (Usuario $usuario): void {
            Demandante::create([
                'usuario_id' => $usuario->id,
                'escola_instituicao_id' => EscolaInstituicao::factory()->create()->id,
                'cargo' => 'Diretor(a)',
            ]);
        });
    }

    public function coordenador(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => Perfil::Coordenador,
        ])->afterCreating(function (Usuario $usuario): void {
            Coordenador::create(['usuario_id' => $usuario->id]);
        });
    }
}
