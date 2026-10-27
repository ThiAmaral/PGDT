<?php

namespace Database\Factories;

use App\Enums\SituacaoEntrega;
use App\Enums\TipoEntrega;
use App\Models\Entrega;
use App\Models\Subprojeto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Entrega do tipo link. O remetente deve ser um membro da equipe do sub-projeto.
 *
 * @extends Factory<Entrega>
 */
class EntregaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subprojeto_id' => Subprojeto::factory(),
            'numero' => fn (array $attributes) => (int) Entrega::where('subprojeto_id', $attributes['subprojeto_id'])->max('numero') + 1,
            'enviado_por' => fn (array $attributes) => Subprojeto::findOrFail($attributes['subprojeto_id'])->coordenador_id,
            'tipo' => TipoEntrega::Link,
            'titulo' => 'Entrega '.fake()->unique()->words(2, true),
            'descricao' => fake()->sentence(),
            'url' => 'https://github.com/exemplo/'.fake()->slug(2),
            'situacao' => SituacaoEntrega::AguardandoTecnica,
        ];
    }

    public function comSituacao(SituacaoEntrega $situacao): static
    {
        return $this->state(fn (array $attributes) => ['situacao' => $situacao]);
    }
}
