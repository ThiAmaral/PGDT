<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    /**
     * Cursos de graduação do campus de Alegre (CCENS e CCAE). O admin pode inativar ou incluir outros.
     */
    public function run(): void
    {
        $cursos = [
            'Agronomia',
            'Ciência da Computação',
            'Ciências Biológicas',
            'Engenharia de Alimentos',
            'Engenharia Florestal',
            'Engenharia Industrial Madeireira',
            'Engenharia Química',
            'Farmácia',
            'Física',
            'Geologia',
            'Matemática',
            'Medicina Veterinária',
            'Nutrição',
            'Química',
            'Sistemas de Informação',
            'Zootecnia',
        ];

        foreach ($cursos as $nome) {
            Curso::firstOrCreate(['nome' => $nome]);
        }
    }
}
