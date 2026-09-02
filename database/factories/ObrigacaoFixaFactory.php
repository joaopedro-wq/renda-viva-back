<?php

namespace Database\Factories;

use App\Models\ObrigacaoFixa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObrigacaoFixa>
 */
class ObrigacaoFixaFactory extends Factory
{
    protected $model = ObrigacaoFixa::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'descricao' => fake()->randomElement(['Aluguel', 'Internet', 'Streaming', 'Academia']),
            'valor' => fake()->randomFloat(2, 30, 2000),
            'dia_vencimento' => fake()->numberBetween(1, 28),
            'ativa' => true,
        ];
    }
}
