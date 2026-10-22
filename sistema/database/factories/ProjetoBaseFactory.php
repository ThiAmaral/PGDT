<?php

namespace Database\Factories;

use App\Models\ProjetoBase;
use App\Models\Tipologia;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjetoBase>
 */
class ProjetoBaseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => 'Projeto '.fake()->unique()->words(3, true),
            'descricao' => fake()->paragraph(),
            'escopo_preliminar' => fake()->sentence(),
            'data_inicio' => now()->startOfMonth(),
            'data_fim' => now()->addYear()->endOfMonth(),
            'tipologia_id' => Tipologia::factory(),
            'coordenador_id' => Usuario::factory()->coordenador(),
            'publico' => true,
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => ['ativo' => false]);
    }
}
