<?php

namespace Database\Factories;

use App\Models\Tipologia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tipologia>
 */
class TipologiaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => 'Tipologia '.fake()->unique()->numerify('#####'),
            'ativo' => true,
        ];
    }
}
