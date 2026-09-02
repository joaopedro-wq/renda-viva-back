<?php

namespace Database\Factories;

use App\Models\MovimentoColchao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MovimentoColchao>
 */
class MovimentoColchaoFactory extends Factory
{
    protected $model = MovimentoColchao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'valor' => fake()->randomFloat(2, 10, 500),
            'tipo' => fake()->randomElement([
                'aporte_automatico', 'aporte_manual', 'saque_automatico', 'saque_manual',
            ]),
            'descricao' => fake()->sentence(4),
            'data' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
        ];
    }
}
