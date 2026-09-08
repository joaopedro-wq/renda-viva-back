<?php

namespace Database\Factories;

use App\Models\ModeloImportacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloImportacao>
 */
class ModeloImportacaoFactory extends Factory
{
    protected $model = ModeloImportacao::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'nome' => 'Nubank Conta',
            'assinatura_colunas' => md5(fake()->uuid()),
            'coluna_data' => 'Data',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => 'Identificador',
            'formato_data' => 'd/m/Y',
            'convencao_sinal' => 'negativo_e_gasto',
        ];
    }
}
