<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Opcional — usuário pode lançar/importar renda sem categorizar. */
    public function up(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->foreignId('categoria_renda_id')
                ->nullable()
                ->after('fonte')
                ->constrained('categorias_renda')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_renda_id');
        });
    }
};
