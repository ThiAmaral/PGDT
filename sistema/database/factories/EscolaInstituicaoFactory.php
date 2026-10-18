<?php

namespace Database\Factories;

use App\Enums\TipoEscola;
use App\Models\EscolaInstituicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EscolaInstituicao>
 */
class EscolaInstituicaoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Escola '.fake()->unique()->lastName(),
            'tipo' => TipoEscola::EscolaPublica,
            'cidade' => 'Alegre',
            'uf' => 'ES',
            'ativo' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn (array $attributes) => ['ativo' => false]);
    }
}
