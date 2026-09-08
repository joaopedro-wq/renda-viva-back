<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapeamento de colunas de um formato de extrato (ex.: "Nubank Conta"),
     * salvo por usuário e reconhecido automaticamente pela assinatura do
     * cabeçalho do CSV nas próximas importações — sem remapear todo mês.
     */
    public function up(): void
    {
        Schema::create('modelos_importacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('nome'); // ex. "Nubank Conta"
            $table->string('assinatura_colunas'); // md5 do cabeçalho, pra reconhecer o formato de novo
            $table->string('coluna_data');
            $table->string('coluna_valor');
            $table->string('coluna_descricao');
            $table->string('coluna_identificador')->nullable(); // dedupe, quando o banco fornece
            $table->string('formato_data'); // ex. "d/m/Y"
            $table->string('convencao_sinal')->default('negativo_e_gasto');
            $table->timestamps();

            $table->unique(['usuario_id', 'assinatura_colunas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modelos_importacao');
    }
};
