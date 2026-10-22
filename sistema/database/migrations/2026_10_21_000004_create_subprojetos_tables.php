<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sub-projetos, candidaturas e equipes (Fase 5, RF09–RF11, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('subprojetos', function (Blueprint $table) {
            $table->id();
            // 1:1 com a demanda. O Projeto Base vem da demanda, sem coluna repetida.
            $table->foreignId('solicitacao_id')->unique()->constrained('solicitacoes')->restrictOnDelete();
            $table->foreignId('coordenador_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('titulo', 150);
            $table->text('escopo');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->unsignedSmallInteger('carga_horaria_estimada');
            $table->unsignedSmallInteger('vagas');
            $table->string('modalidade', 15)->index();
            // Controle manual do coordenador; a equipe completa também fecha as inscrições.
            $table->boolean('candidaturas_abertas')->default(true);
            $table->timestamps();
        });

        Schema::create('candidaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subprojeto_id')->constrained('subprojetos')->restrictOnDelete();
            $table->foreignId('estudante_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('status', 15)->default('pendente')->index();
            $table->foreignId('avaliado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('avaliado_em')->nullable();
            $table->timestamps();

            $table->unique(['subprojeto_id', 'estudante_id']);
        });

        Schema::create('papeis_equipe', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('membros_equipe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subprojeto_id')->constrained('subprojetos')->restrictOnDelete();
            $table->foreignId('estudante_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('papel_id')->constrained('papeis_equipe')->restrictOnDelete();
            // Nulo se o membro não veio de uma candidatura.
            $table->foreignId('candidatura_id')->nullable()->unique()->constrained('candidaturas')->restrictOnDelete();
            // Remoção sem apagar o histórico de participação.
            $table->timestamp('removido_em')->nullable();
            $table->timestamps();

            $table->unique(['subprojeto_id', 'estudante_id']);
        });

        // Papéis iniciais (DER, ponto 3). O admin cadastra outros nos cadastros de apoio.
        foreach (['Membro Desenvolvedor', 'Responsável Técnico', 'Apoio de Campo'] as $nome) {
            DB::table('papeis_equipe')->insert(['nome' => $nome, 'ativo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membros_equipe');
        Schema::dropIfExists('papeis_equipe');
        Schema::dropIfExists('candidaturas');
        Schema::dropIfExists('subprojetos');
    }
};
