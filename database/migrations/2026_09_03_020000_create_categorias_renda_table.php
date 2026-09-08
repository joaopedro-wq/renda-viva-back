<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global — mesmo padrão de `categorias_gasto` (nome, ícone
     * Lucide em kebab-case, cor), sem `usuario_id`. Ver `CategoriaRendaSeeder`.
     */
    public function up(): void
    {
        Schema::create('categorias_renda', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('icone');
            $table->string('cor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_renda');
    }
};
