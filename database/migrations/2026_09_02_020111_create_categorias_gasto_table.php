<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global (sem usuario_id) seedado — não é cadastro pessoal, só um
     * conjunto fixo de categorias pra classificar gastos. Ver CategoriaGastoSeeder.
     */
    public function up(): void
    {
        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('icone'); // nome do ícone Lucide, ex. "utensils"
            $table->string('cor', 7); // hex, ex. "#2f6f6b"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_gasto');
    }
};
