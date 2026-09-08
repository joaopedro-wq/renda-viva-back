<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ver comentário da migration equivalente em `rendas`. */
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->string('origem_externa_id')->nullable()->after('data');

            $table->unique(['usuario_id', 'origem_externa_id']);
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropUnique(['usuario_id', 'origem_externa_id']);
            $table->dropColumn('origem_externa_id');
        });
    }
};
