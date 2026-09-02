<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Aluguel, assinaturas — sempre descontadas primeiro no cálculo semanal. */
    public function up(): void
    {
        Schema::create('obrigacoes_fixas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('descricao');
            $table->decimal('valor', 12, 2);
            $table->unsignedTinyInteger('dia_vencimento'); // 1-31, validado no Form Request
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obrigacoes_fixas');
    }
};
