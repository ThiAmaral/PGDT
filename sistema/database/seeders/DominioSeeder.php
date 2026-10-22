<?php

namespace Database\Seeders;

use App\Models\CategoriaDemanda;
use App\Models\PapelEquipe;
use App\Models\Prioridade;
use App\Models\Tipologia;
use Illuminate\Database\Seeder;

class DominioSeeder extends Seeder
{
    /**
     * Valores iniciais das tabelas de domínio. O admin pode inativar, renomear ou incluir outros.
     */
    public function run(): void
    {
        // Modalidades de ação de extensão da Política Nacional de Extensão (FORPROEX).
        foreach (['Programa', 'Projeto', 'Curso', 'Oficina', 'Evento', 'Prestação de Serviço'] as $nome) {
            Tipologia::firstOrCreate(['nome' => $nome]);
        }

        $categorias = [
            'Desenvolvimento de sistema ou aplicativo',
            'Site e presença digital',
            'Redes e conectividade',
            'Manutenção de computadores e equipamentos',
            'Capacitação e formação',
            'Consultoria e diagnóstico',
            'Outros',
        ];

        foreach ($categorias as $nome) {
            CategoriaDemanda::firstOrCreate(['nome' => $nome]);
        }

        foreach (['Urgente', 'Alta', 'Média', 'Baixa'] as $ordem => $nome) {
            Prioridade::firstOrCreate(['nome' => $nome], ['ordem' => ($ordem + 1) * 10]);
        }

        // Papéis iniciais da equipe do sub-projeto (DER, ponto 3).
        foreach (['Membro Desenvolvedor', 'Responsável Técnico', 'Apoio de Campo'] as $nome) {
            PapelEquipe::firstOrCreate(['nome' => $nome]);
        }
    }
}
