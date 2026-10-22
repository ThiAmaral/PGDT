<?php

namespace Database\Factories;

use App\Enums\Complexidade;
use App\Enums\StatusDemanda;
use App\Models\CategoriaDemanda;
use App\Models\Prioridade;
use App\Models\ProjetoBase;
use App\Models\Solicitacao;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Solicitacao>
 */
class SolicitacaoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'projeto_base_id' => ProjetoBase::factory(),
            'demandante_id' => Usuario::factory()->demandante(),
            // A escola vem do demandante, como na abertura pelo formulário.
            'escola_instituicao_id' => fn (array $attributes) => Usuario::findOrFail($attributes['demandante_id'])->demandante->escola_instituicao_id,
            'titulo' => 'Demanda '.fake()->unique()->words(3, true),
            'descricao' => fake()->paragraph(),
            'justificativa' => fake()->sentence(),
            'publico_afetado' => 'Alunos do ensino fundamental',
            'status' => StatusDemanda::Cadastrada,
        ];
    }

    public function comStatus(StatusDemanda $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    /**
     * Demanda já classificada pelo coordenador.
     */
    public function triada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusDemanda::EmAnalise,
            'categoria_id' => CategoriaDemanda::create(['nome' => 'Categoria '.fake()->unique()->numerify('#####')])->id,
            'prioridade_id' => Prioridade::create(['nome' => 'Prioridade '.fake()->unique()->numerify('#####'), 'ordem' => 1])->id,
            'complexidade' => Complexidade::Media,
            'triado_em' => now(),
        ]);
    }
}
