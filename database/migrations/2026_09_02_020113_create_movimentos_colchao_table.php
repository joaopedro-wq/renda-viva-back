<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Livro-razão do colchão — nunca um saldo solto no usuário. Saldo atual =
     * SUM(valor) por usuario_id. `valor` pode ser negativo (saque). `tipo` é
     * `enum` (Laravel emite CHECK constraint no Postgres — rígido o bastante
     * sem precisar de CREATE TYPE manual).
     */
    public function up(): void
    {
        Schema::create('movimentos_colchao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('valor', 12, 2); // + aporte, - saque
            $table->enum('tipo', [
                'aporte_automatico',
                'aporte_manual',
                'saque_automatico',
                'saque_manual',
            ]);
            $table->string('descricao')->nullable();
            $table->date('data');
            $table->timestamps();

            $table->index(['usuario_id', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentos_colchao');
    }
};
