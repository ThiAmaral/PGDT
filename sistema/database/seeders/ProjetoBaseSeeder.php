<?php

namespace Database\Seeders;

use App\Models\ProjetoBase;
use App\Models\Tipologia;
use Illuminate\Database\Seeder;

class ProjetoBaseSeeder extends Seeder
{
    /**
     * Projeto Base "Demandas Gerais" (decisão da Fase 0): recebe as demandas que não se encaixam
     * em nenhum outro projeto. Nasce sem coordenador; o admin define quem coordena.
     */
    public function run(): void
    {
        ProjetoBase::firstOrCreate(['titulo' => ProjetoBase::DEMANDAS_GERAIS], [
            'descricao' => 'Demandas de apoio tecnológico das escolas e instituições que não se encaixam em nenhum outro projeto de extensão.',
            'data_inicio' => now()->startOfYear(),
            'tipologia_id' => Tipologia::firstOrCreate(['nome' => 'Programa'])->id,
        ]);
    }
}
