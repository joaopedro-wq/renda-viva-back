<?php

namespace Tests\Unit;

use App\Models\CategoriaGasto;
use App\Services\ClassificacaoImportService;
use App\Services\LinhaExtrato;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClassificacaoImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClassificacaoImportService $service;

    private Collection $categorias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ClassificacaoImportService;
        $this->categorias = collect([
            CategoriaGasto::create(['nome' => 'Alimentação', 'icone' => 'utensils', 'cor' => '#000']),
            CategoriaGasto::create(['nome' => 'Transporte', 'icone' => 'car', 'cor' => '#000']),
            CategoriaGasto::create(['nome' => 'Lazer', 'icone' => 'gamepad-2', 'cor' => '#000']),
        ]);
    }

    private function linha(int $indice, float $valor, string $descricao, ?string $id = null): LinhaExtrato
    {
        return new LinhaExtrato($indice, '2026-08-15', $valor, $descricao, $id);
    }

    public function test_valor_positivo_e_sugerido_como_renda(): void
    {
        $linhas = [$this->linha(0, 80.00, 'Transferência recebida pelo Pix - Lucas Ferreira Milagre')];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertSame('renda', $resultado[0]->tipoSugerido);
        $this->assertNull($resultado[0]->motivo);
        $this->assertNull($resultado[0]->categoriaSugeridaId);
    }

    public function test_renda_com_descricao_ja_categorizada_antes_sugere_a_mesma_categoria(): void
    {
        $linhas = [$this->linha(0, 3500.00, 'Transferência recebida pelo Pix - EMPRESA XYZ LTDA')];

        $resultado = $this->service->classificar(
            $linhas,
            [],
            $this->categorias,
            ['Transferência recebida pelo Pix - EMPRESA XYZ LTDA' => 42]
        );

        $this->assertSame('renda', $resultado[0]->tipoSugerido);
        $this->assertSame(42, $resultado[0]->categoriaSugeridaId);
    }

    public function test_renda_sem_historico_pra_essa_descricao_nao_sugere_categoria(): void
    {
        $linhas = [$this->linha(0, 80.00, 'Transferência recebida pelo Pix - Alguém novo')];

        $resultado = $this->service->classificar(
            $linhas,
            [],
            $this->categorias,
            ['Outra descrição qualquer' => 42]
        );

        $this->assertNull($resultado[0]->categoriaSugeridaId);
    }

    public function test_valor_negativo_sem_categoria_reconhecida_e_gasto_sem_categoria(): void
    {
        $linhas = [$this->linha(0, -742.59, 'Pagamento de fatura')];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertSame('gasto', $resultado[0]->tipoSugerido);
        $this->assertNull($resultado[0]->categoriaSugeridaId);
    }

    public function test_debito_em_conta_e_gasto_sem_categoria(): void
    {
        $linhas = [$this->linha(0, -20.00, 'Débito em conta')];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertSame('gasto', $resultado[0]->tipoSugerido);
        $this->assertNull($resultado[0]->categoriaSugeridaId);
    }

    public function test_sugere_categoria_por_palavra_chave(): void
    {
        $linhas = [
            $this->linha(0, -15.93, 'Transferência enviada pelo Pix - UBER DO BRASIL TECNOLOGIA LTDA.'),
            $this->linha(1, -67.98, 'Transferência enviada pelo Pix - CACAU SHOW'),
            $this->linha(2, -165.00, 'Transferência enviada pelo Pix - CINEMARK'),
        ];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $transporte = $this->categorias->firstWhere('nome', 'Transporte');
        $alimentacao = $this->categorias->firstWhere('nome', 'Alimentação');
        $lazer = $this->categorias->firstWhere('nome', 'Lazer');

        $this->assertSame($transporte->id, $resultado[0]->categoriaSugeridaId);
        $this->assertSame($alimentacao->id, $resultado[1]->categoriaSugeridaId);
        $this->assertSame($lazer->id, $resultado[2]->categoriaSugeridaId);
    }

    public function test_par_lavado_marca_a_perna_positiva_pra_ignorar(): void
    {
        $id = '6a6fc7ff-9f57-4369-b77b-419b307b54cb';
        $linhas = [
            $this->linha(0, 46.38, 'Valor adicionado na conta por cartão de crédito', $id),
            $this->linha(1, -46.38, 'Transferência enviada pelo Pix - IFOOD.COM', $id),
        ];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertSame('ignorar', $resultado[0]->tipoSugerido);
        $this->assertSame('par_lavado', $resultado[0]->motivo);

        // A perna negativa (gasto real no iFood) segue normal, categorizável.
        $this->assertSame('gasto', $resultado[1]->tipoSugerido);
        $this->assertSame(
            $this->categorias->firstWhere('nome', 'Alimentação')->id,
            $resultado[1]->categoriaSugeridaId
        );
    }

    public function test_mercado_pago_nao_e_categorizado_como_alimentacao(): void
    {
        // Falso positivo real, achado testando contra um extrato do Nubank:
        // "MERCADO" sozinho batia em "MERCADO PAGO" (intermediário de
        // pagamento), não em compra de supermercado.
        $linhas = [$this->linha(0, -10.00, 'Transferência enviada pelo Pix - MERCADO PAGO IP LTDA.')];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertNull($resultado[0]->categoriaSugeridaId);
    }

    public function test_mesmo_identificador_com_valores_que_nao_se_cancelam_nao_e_par_lavado(): void
    {
        $id = 'mesmo-id-mas-valores-diferentes';
        $linhas = [
            $this->linha(0, 100.00, 'Renda qualquer', $id),
            $this->linha(1, -50.00, 'Gasto qualquer', $id),
        ];

        $resultado = $this->service->classificar($linhas, [], $this->categorias);

        $this->assertSame('renda', $resultado[0]->tipoSugerido);
        $this->assertSame('gasto', $resultado[1]->tipoSugerido);
    }

    public function test_linha_com_identificador_ja_importado_e_ignorada(): void
    {
        $linhas = [$this->linha(0, -13.00, 'Transferência qualquer', 'id-ja-importado')];

        $resultado = $this->service->classificar($linhas, ['id-ja-importado'], $this->categorias);

        $this->assertSame('ignorar', $resultado[0]->tipoSugerido);
        $this->assertTrue($resultado[0]->jaImportado);
        $this->assertSame('ja_importado', $resultado[0]->motivo);
    }
}
