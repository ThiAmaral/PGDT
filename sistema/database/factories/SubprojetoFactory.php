<?php

namespace Database\Factories;

use App\Enums\Modalidade;
use App\Enums\StatusDemanda;
use App\Models\Solicitacao;
use App\Models\Subprojeto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subprojeto>
 */
class SubprojetoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solicitacao_id' => Solicitacao::factory()->triada()->comStatus(StatusDemanda::Priorizada),
            // O responsável é o coordenador do Projeto Base da demanda.
            'coordenador_id' => fn (array $attributes) => Solicitacao::findOrFail($attributes['solicitacao_id'])->projetoBase->coordenador_id,
            'titulo' => fn (array $attributes) => Solicitacao::findOrFail($attributes['solicitacao_id'])->titulo,
            'escopo' => fake()->paragraph(),
            'data_inicio' => now()->startOfMonth()->toDateString(),
            'data_fim' => now()->addMonths(4)->endOfMonth()->toDateString(),
            'carga_horaria_estimada' => 40,
            'vagas' => 3,
            'modalidade' => Modalidade::Remoto,
            'candidaturas_abertas' => true,
        ];
    }

    public function fechado(): static
    {
        return $this->state(fn (array $attributes) => ['candidaturas_abertas' => false]);
    }
}
