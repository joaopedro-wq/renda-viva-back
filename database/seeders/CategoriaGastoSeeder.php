<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use Illuminate\Database\Seeder;

/**
 * Catálogo global de categorias — idempotente (updateOrCreate por nome), pode
 * rodar de novo sem duplicar. `icone` usa o nome do ícone Lucide (kebab-case,
 * mesmo catálogo do @lucide/angular no front) sem o prefixo "lucide".
 */
class CategoriaGastoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nome' => 'Alimentação', 'icone' => 'utensils', 'cor' => '#d98e4a'],
            ['nome' => 'Moradia', 'icone' => 'home', 'cor' => '#2f6f6b'],
            ['nome' => 'Transporte', 'icone' => 'car', 'cor' => '#4a7fb5'],
            ['nome' => 'Saúde', 'icone' => 'heart-pulse', 'cor' => '#c25b6b'],
            ['nome' => 'Lazer', 'icone' => 'gamepad-2', 'cor' => '#8a5fc2'],
            ['nome' => 'Educação', 'icone' => 'graduation-cap', 'cor' => '#5c6bc0'],
            ['nome' => 'Compras', 'icone' => 'shopping-bag', 'cor' => '#c2954a'],
            ['nome' => 'Assinaturas', 'icone' => 'repeat', 'cor' => '#6b7a8a'],
            ['nome' => 'Família e pets', 'icone' => 'heart', 'cor' => '#c2708a'],
            ['nome' => 'Outros', 'icone' => 'more-horizontal', 'cor' => '#8a8a8a'],
        ];

        foreach ($categorias as $categoria) {
            CategoriaGasto::updateOrCreate(['nome' => $categoria['nome']], $categoria);
        }
    }
}
