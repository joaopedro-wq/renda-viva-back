<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dedupe de importação: guarda o identificador da transação no extrato de
     * origem (ex.: UUID do Nubank). Reimportar o mesmo arquivo/mês sobreposto
     * pula linhas já importadas em vez de duplicar.
     */
    public function up(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->string('origem_externa_id')->nullable()->after('recorrente');

            $table->unique(['usuario_id', 'origem_externa_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->dropUnique(['usuario_id', 'origem_externa_id']);
            $table->dropColumn('origem_externa_id');
        });
    }
};
