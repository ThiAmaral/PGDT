<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Demandas e anexos (Fase 3, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projeto_base_id')->constrained('projetos_base')->restrictOnDelete();
            $table->foreignId('demandante_id')->constrained('usuarios')->restrictOnDelete();
            // Copiada do demandante na abertura, para os relatórios não mudarem se ele trocar de escola.
            $table->foreignId('escola_instituicao_id')->constrained('escolas_instituicoes')->restrictOnDelete();
            $table->string('titulo', 100);
            $table->text('descricao');
            $table->text('justificativa');
            $table->string('publico_afetado', 255);
            $table->string('status', 30)->default('cadastrada')->index();
            $table->text('motivo_status')->nullable();
            // Triagem (RF07): nulos até o coordenador classificar a demanda.
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_demanda')->restrictOnDelete();
            $table->foreignId('prioridade_id')->nullable()->constrained('prioridades')->restrictOnDelete();
            $table->string('complexidade', 10)->nullable();
            $table->foreignId('triado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('triado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes')->cascadeOnDelete();
            $table->string('nome_original', 255);
            // Caminho no disco "local" (storage/app/private), fora da pasta pública.
            $table->string('caminho', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('tamanho_bytes');
            $table->foreignId('enviado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos');
        Schema::dropIfExists('solicitacoes');
    }
};
