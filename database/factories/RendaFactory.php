<?php

namespace Database\Factories;

use App\Models\Renda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Renda>
 */
class RendaFactory extends Factory
{
    protected $model = Renda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'descricao' => fake()->words(3, true),
            'fonte' => fake()->randomElement(['Freela', 'Uber', 'Venda', 'Consultoria']),
            'valor' => fake()->randomFloat(2, 50, 3000),
            'data_recebimento' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'recorrente' => fake()->boolean(),
        ];
    }
}
