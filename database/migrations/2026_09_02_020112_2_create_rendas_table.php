<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lançamento de renda. `data_recebimento` é a data real em que o dinheiro
     * entrou — nunca uma data projetada/esperada; é o que sustenta o "modo
     * prudente" do cálculo de quanto dá pra gastar.
     */
    public function up(): void
    {
        Schema::create('rendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('descricao')->nullable();
            $table->string('fonte')->nullable(); // texto livre, ex. "Freela Cliente X"
            $table->decimal('valor', 12, 2);
            $table->date('data_recebimento');
            $table->boolean('recorrente')->default(false); // pesa mais na linha de base
            $table->timestamps();

            $table->index(['usuario_id', 'data_recebimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendas');
    }
};
