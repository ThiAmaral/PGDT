<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parâmetros gerais editados pelo admin (Fase 7). A lista de parâmetros, os valores padrão
     * e as regras ficam no model Parametro; a tabela guarda só os valores alterados.
     */
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->string('chave', 50)->primary();
            $table->text('valor')->nullable();
            $table->foreignId('atualizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
