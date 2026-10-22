<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Histórico e chat da demanda (Fase 4, RF08, ver docs/DER.md).
     */
    public function up(): void
    {
        Schema::create('historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes')->cascadeOnDelete();
            // Nulo só quando a mudança não veio de um usuário logado (ex.: comando de manutenção).
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->string('tipo', 30)->index();
            $table->string('descricao', 255);
            $table->json('dados')->nullable();
            // Somente inclusão: o histórico nunca é editado.
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            // Perfil do autor no momento do envio, para a conversa não mudar se o perfil for trocado.
            $table->string('perfil', 20);
            $table->text('texto');
            $table->timestamp('created_at')->useCurrent();
        });

        // Demandas abertas antes desta fase ganham o registro de abertura na linha do tempo.
        DB::table('solicitacoes')->orderBy('id')->each(function (object $solicitacao): void {
            DB::table('historicos')->insert([
                'solicitacao_id' => $solicitacao->id,
                'usuario_id' => $solicitacao->demandante_id,
                'tipo' => 'status',
                'descricao' => 'Demanda aberta',
                'dados' => json_encode(['de' => null, 'para' => 'cadastrada']),
                'created_at' => $solicitacao->created_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens');
        Schema::dropIfExists('historicos');
    }
};
