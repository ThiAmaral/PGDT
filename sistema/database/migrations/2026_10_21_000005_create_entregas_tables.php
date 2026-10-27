<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entregas da equipe e validações (Fase 6, RF12 e RF13, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subprojeto_id')->constrained('subprojetos')->restrictOnDelete();
            $table->foreignId('enviado_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('tipo', 20);
            $table->string('titulo', 150);
            $table->text('descricao')->nullable();
            $table->string('url', 500)->nullable();
            // Resultado da última validação; evita recalcular a partir de `validacoes` em cada lista.
            $table->string('situacao', 25)->default('aguardando_tecnica')->index();
            $table->timestamps();
        });

        Schema::create('entrega_arquivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->restrictOnDelete();
            $table->string('nome_original', 255);
            $table->string('caminho', 255);
            $table->string('mime', 100);
            $table->unsignedInteger('tamanho_bytes');
            $table->timestamps();
        });

        Schema::create('validacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->constrained('entregas')->restrictOnDelete();
            $table->string('etapa', 15);
            $table->string('decisao', 20);
            $table->text('feedback')->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validacoes');
        Schema::dropIfExists('entrega_arquivos');
        Schema::dropIfExists('entregas');
    }
};
