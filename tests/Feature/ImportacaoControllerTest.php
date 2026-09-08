<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\CategoriaRenda;
use App\Models\Gasto;
use App\Models\ModeloImportacao;
use App\Models\Renda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function csvNubank(): string
    {
        return <<<'CSV'
        Data,Valor,Identificador,Descrição
        01/08/2026,-13.00,id-1,Transferência enviada pelo Pix - Zenira Lopes Da Silva
        02/08/2026,46.38,id-2,Valor adicionado na conta por cartão de crédito
        02/08/2026,-46.38,id-2,Transferência enviada pelo Pix - IFOOD.COM AGENCIA
        03/08/2026,3419.24,id-3,Transferência recebida pelo Pix - CO-CREATORS COLLAB LTDA
        CSV;
    }

    private function csvFormatoDesconhecido(): string
    {
        return <<<'CSV'
        Date,Amount,Description
        01/08/2026,-13.00,Some transaction
        CSV;
    }

    private function arquivo(string $conteudo, string $nome = 'extrato.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nome, $conteudo);
    }

    public function test_pre_visualizar_pede_mapeamento_quando_nao_reconhece_o_cabecalho(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvFormatoDesconhecido()),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('precisa_mapear', true)
            ->assertJsonPath('cabecalho', ['Date', 'Amount', 'Description']);
    }

    public function test_pre_visualizar_reconhece_formato_nubank_conta_automaticamente(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvNubank()),
        ]);

        $response->assertStatus(200)->assertJsonPath('precisa_mapear', false);
        $this->assertDatabaseHas('modelos_importacao', ['usuario_id' => $usuario->id, 'nome' => 'Nubank Conta']);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_pre_visualizar_com_mapeamento_cria_modelo_e_classifica(): void
    {
        $usuario = User::factory()->create();
        CategoriaGasto::create(['nome' => 'Alimentação', 'icone' => 'utensils', 'cor' => '#000']);

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvNubank()),
            'mapeamento' => [
                'nome' => 'Nubank Conta',
                'coluna_data' => 'Data',
                'coluna_valor' => 'Valor',
                'coluna_descricao' => 'Descrição',
                'coluna_identificador' => 'Identificador',
                'formato_data' => 'd/m/Y',
            ],
        ]);

        $response->assertStatus(200)->assertJsonPath('precisa_mapear', false);
        $this->assertDatabaseHas('modelos_importacao', ['usuario_id' => $usuario->id, 'nome' => 'Nubank Conta']);

        $linhas = $response->json('data');
        $this->assertCount(4, $linhas);

        // Par lavado: a entrada (id-2, +46.38) é ignorada, a saída (id-2, -46.38) vira gasto.
        $this->assertSame('ignorar', $linhas[1]['tipo_sugerido']);
        $this->assertSame('par_lavado', $linhas[1]['motivo']);
        $this->assertSame('gasto', $linhas[2]['tipo_sugerido']);

        $this->assertSame('renda', $linhas[3]['tipo_sugerido']);
    }

    public function test_pre_visualizar_reconhece_modelo_salvo_pela_assinatura_do_cabecalho(): void
    {
        $usuario = User::factory()->create();
        $modelo = ModeloImportacao::factory()->for($usuario, 'usuario')->create([
            'assinatura_colunas' => md5('Data|Valor|Identificador|Descrição'),
            'coluna_data' => 'Data',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => 'Identificador',
            'formato_data' => 'd/m/Y',
        ]);

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvNubank()),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('precisa_mapear', false)
            ->assertJsonPath('modelo_importacao_id', $modelo->id);
    }

    public function test_pre_visualizar_marca_linha_ja_importada(): void
    {
        $usuario = User::factory()->create();
        Renda::factory()->for($usuario, 'usuario')->create(['origem_externa_id' => 'id-3']);

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvNubank()),
            'mapeamento' => [
                'nome' => 'Nubank Conta',
                'coluna_data' => 'Data',
                'coluna_valor' => 'Valor',
                'coluna_descricao' => 'Descrição',
                'coluna_identificador' => 'Identificador',
                'formato_data' => 'd/m/Y',
            ],
        ]);

        $linhas = $response->json('data');
        $this->assertTrue($linhas[3]['ja_importado']);
        $this->assertSame('ignorar', $linhas[3]['tipo_sugerido']);
    }

    public function test_pre_visualizar_sugere_categoria_de_renda_aprendida_do_historico(): void
    {
        $usuario = User::factory()->create();
        $salario = CategoriaRenda::create(['nome' => 'Salário', 'icone' => 'briefcase', 'cor' => '#000']);

        // Renda anterior com a MESMA descrição do extrato, já categorizada — é
        // o que o próximo import deve "lembrar".
        Renda::factory()->for($usuario, 'usuario')->create([
            'descricao' => 'Transferência recebida pelo Pix - CO-CREATORS COLLAB LTDA',
            'categoria_renda_id' => $salario->id,
        ]);

        $response = $this->actingAs($usuario, 'sanctum')->post('/api/importacoes/pre-visualizar', [
            'arquivo' => $this->arquivo($this->csvNubank()),
            'mapeamento' => [
                'nome' => 'Nubank Conta',
                'coluna_data' => 'Data',
                'coluna_valor' => 'Valor',
                'coluna_descricao' => 'Descrição',
                'coluna_identificador' => 'Identificador',
                'formato_data' => 'd/m/Y',
            ],
        ]);

        $linhaRenda = $response->json('data.3');
        $this->assertSame('renda', $linhaRenda['tipo_sugerido']);
        $this->assertSame($salario->id, $linhaRenda['categoria_sugerida_id']);
    }

    public function test_pre_visualizar_exige_autenticacao(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->post('/api/importacoes/pre-visualizar', ['arquivo' => $this->arquivo($this->csvNubank())])
            ->assertStatus(401);
    }

    public function test_confirmar_grava_rendas_e_gastos(): void
    {
        $usuario = User::factory()->create();
        $categoria = CategoriaGasto::create(['nome' => 'Alimentação', 'icone' => 'utensils', 'cor' => '#000']);

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/importacoes/confirmar', [
            'linhas' => [
                [
                    'data' => '2026-08-03',
                    'valor' => 3419.24,
                    'descricao' => 'Pix recebido',
                    'identificador_externo' => 'id-3',
                    'tipo' => 'renda',
                    'categoria_gasto_id' => null,
                ],
                [
                    'data' => '2026-08-02',
                    'valor' => -46.38,
                    'descricao' => 'IFOOD',
                    'identificador_externo' => 'id-2',
                    'tipo' => 'gasto',
                    'categoria_gasto_id' => $categoria->id,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.rendas_criadas', 1)
            ->assertJsonPath('data.gastos_criados', 1)
            ->assertJsonPath('data.ja_existiam', 0);

        $this->assertDatabaseHas('rendas', [
            'usuario_id' => $usuario->id,
            'origem_externa_id' => 'id-3',
            'valor' => 3419.24,
        ]);
        $this->assertDatabaseHas('gastos', [
            'usuario_id' => $usuario->id,
            'origem_externa_id' => 'id-2',
            'valor' => 46.38, // sempre grava magnitude positiva, mesmo linha vindo negativa do extrato
            'categoria_gasto_id' => $categoria->id,
        ]);
    }

    public function test_confirmar_grava_categoria_de_renda(): void
    {
        $usuario = User::factory()->create();
        $salario = CategoriaRenda::create(['nome' => 'Salário', 'icone' => 'briefcase', 'cor' => '#000']);

        $this->actingAs($usuario, 'sanctum')->postJson('/api/importacoes/confirmar', [
            'linhas' => [[
                'data' => '2026-08-03',
                'valor' => 3419.24,
                'descricao' => 'Pix recebido',
                'identificador_externo' => 'id-3',
                'tipo' => 'renda',
                'categoria_renda_id' => $salario->id,
            ]],
        ])->assertStatus(200);

        $this->assertDatabaseHas('rendas', [
            'usuario_id' => $usuario->id,
            'origem_externa_id' => 'id-3',
            'categoria_renda_id' => $salario->id,
        ]);
    }

    public function test_confirmar_e_idempotente_pra_linha_ja_importada(): void
    {
        $usuario = User::factory()->create();
        Gasto::factory()->for($usuario, 'usuario')->create(['origem_externa_id' => 'id-2']);

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/importacoes/confirmar', [
            'linhas' => [[
                'data' => '2026-08-02',
                'valor' => -46.38,
                'descricao' => 'IFOOD',
                'identificador_externo' => 'id-2',
                'tipo' => 'gasto',
                'categoria_gasto_id' => null,
            ]],
        ]);

        $response->assertStatus(200)->assertJsonPath('data.ja_existiam', 1)->assertJsonPath('data.gastos_criados', 0);
        $this->assertDatabaseCount('gastos', 1); // não duplicou
    }

    public function test_confirmar_exige_autenticacao(): void
    {
        $this->postJson('/api/importacoes/confirmar', ['linhas' => []])->assertStatus(401);
    }

    public function test_confirmar_valida_linhas_vazias(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario, 'sanctum')
            ->postJson('/api/importacoes/confirmar', ['linhas' => []])
            ->assertStatus(422);
    }
}
