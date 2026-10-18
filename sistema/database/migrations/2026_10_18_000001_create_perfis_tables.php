<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas de apoio e tabelas 1:1 de cada perfil (Fase 1, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150)->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('escolas_instituicoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 200);
            $table->string('tipo', 30);
            $table->string('codigo_inep', 8)->nullable()->unique();
            $table->string('cidade', 100);
            $table->char('uf', 2);
            $table->string('telefone', 20)->nullable();
            $table->string('email', 150)->nullable();
            // Escola digitada pelo demandante nasce inativa e é ativada pelo admin na aprovação.
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('estudantes', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->restrictOnDelete();
            $table->string('matricula', 20)->unique();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('demandantes', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('escola_instituicao_id')->constrained('escolas_instituicoes')->restrictOnDelete();
            $table->string('cargo', 100);
            $table->timestamps();
        });

        Schema::create('coordenadores', function (Blueprint $table) {
            $table->foreignId('usuario_id')->primary()->constrained('usuarios')->restrictOnDelete();
            $table->string('siape', 20)->nullable()->unique();
            $table->string('departamento', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('convites', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->index();
            $table->string('perfil', 20);
            $table->char('token_hash', 64)->unique();
            $table->foreignId('criado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('expira_em');
            $table->timestamp('usado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convites');
        Schema::dropIfExists('coordenadores');
        Schema::dropIfExists('demandantes');
        Schema::dropIfExists('estudantes');
        Schema::dropIfExists('escolas_instituicoes');
        Schema::dropIfExists('cursos');
    }
};
