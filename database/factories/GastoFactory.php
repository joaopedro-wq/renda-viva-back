<?php

namespace Database\Factories;

use App\Models\Gasto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    protected $model = Gasto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'categoria_gasto_id' => null,
            'obrigacao_fixa_id' => null,
            'descricao' => fake()->words(3, true),
            'valor' => fake()->randomFloat(2, 5, 500),
            'data' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
        ];
    }
}
