<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ordem das entregas dentro do sub-projeto (Entrega nº 1, nº 2...), decisão (a) da Fase 6:
     * a equipe pode ter várias entregas em avaliação ao mesmo tempo.
     */
    public function up(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->unsignedSmallInteger('numero')->nullable()->after('subprojeto_id');
        });

        // Numera as entregas que já existiam pela ordem de envio.
        DB::table('entregas')->orderBy('created_at')->orderBy('id')->get(['id', 'subprojeto_id'])
            ->groupBy('subprojeto_id')
            ->each(fn ($entregas) => $entregas->values()->each(
                fn ($entrega, $indice) => DB::table('entregas')->where('id', $entrega->id)->update(['numero' => $indice + 1])
            ));

        Schema::table('entregas', function (Blueprint $table) {
            $table->unsignedSmallInteger('numero')->nullable(false)->change();
            $table->unique(['subprojeto_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropUnique(['subprojeto_id', 'numero']);
            $table->dropColumn('numero');
        });
    }
};
