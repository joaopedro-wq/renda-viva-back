<?php

namespace Database\Seeders;

use App\Models\CategoriaRenda;
use Illuminate\Database\Seeder;

/**
 * Catálogo global — idempotente (updateOrCreate por nome), mesmo padrão do
 * CategoriaGastoSeeder. `icone` usa o nome do ícone Lucide em kebab-case.
 */
class CategoriaRendaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Salário', 'icone' => 'briefcase', 'cor' => '#2f6f6b'],
            ['nome' => 'Freelance', 'icone' => 'laptop', 'cor' => '#4a7fb5'],
            ['nome' => 'Vendas', 'icone' => 'shopping-cart', 'cor' => '#d98e4a'],
            ['nome' => 'Comissão', 'icone' => 'percent', 'cor' => '#8a5fc2'],
            ['nome' => 'Investimentos', 'icone' => 'trending-up', 'cor' => '#5c9e5c'],
            ['nome' => 'Outros', 'icone' => 'more-horizontal', 'cor' => '#8a8a8a'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaRenda::updateOrCreate(['nome' => $categoria['nome']], $categoria);
        }
    }
}
