<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lançamento de gasto. `categoria_gasto_id` é opcional (usuário pode
     * lançar sem categorizar na hora). `obrigacao_fixa_id` só é preenchido
     * quando o gasto liquida uma obrigação fixa do mês — não obriga o usuário
     * a linkar manualmente, mas guarda a referência quando fizer sentido.
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('categoria_gasto_id')->nullable()->constrained('categorias_gasto')->nullOnDelete();
            $table->foreignId('obrigacao_fixa_id')->nullable()->constrained('obrigacoes_fixas')->nullOnDelete();
            $table->string('descricao')->nullable();
            $table->decimal('valor', 12, 2);
            $table->date('data');
            $table->timestamps();

            $table->index(['usuario_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
