<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas de domínio e Projetos Base (Fase 2, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('tipologias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('categorias_demanda', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('prioridades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            // Ordena a fila de triagem: menor número aparece primeiro.
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('projetos_base', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->text('descricao');
            $table->text('escopo_preliminar')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->foreignId('tipologia_id')->constrained('tipologias')->restrictOnDelete();
            // Nulo só no "Demandas Gerais" criado pelo seeder, até o admin definir o coordenador.
            $table->foreignId('coordenador_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->boolean('publico')->default(true);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projetos_base');
        Schema::dropIfExists('prioridades');
        Schema::dropIfExists('categorias_demanda');
        Schema::dropIfExists('tipologias');
    }
};
