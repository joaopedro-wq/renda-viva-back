<?php

namespace Tests\Unit;

use App\Models\ModeloImportacao;
use App\Services\ExtratoParserService;
use RuntimeException;
use Tests\TestCase;

class ExtratoParserServiceTest extends TestCase
{
    private ExtratoParserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExtratoParserService;
    }

    private function modeloNubank(): ModeloImportacao
    {
        return new ModeloImportacao([
            'coluna_data' => 'Data',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => 'Identificador',
            'formato_data' => 'd/m/Y',
        ]);
    }

    private function csvNubank(): string
    {
        return <<<'CSV'
        Data,Valor,Identificador,Descrição
        01/08/2026,-13.00,6a6df54a-7616-4c5a-badd-39273b6dada9,Transferência enviada pelo Pix - Zenira Lopes Da Silva
        03/08/2026,3419.24,6a7013e4-6709-4314-91df-2b22746cbdc9,Transferência recebida pelo Pix - CO-CREATORS COLLAB LTDA
        13/08/2026,-742.59,6a7dbcd8-2b50-45f2-ba96-04a9cf822dc0,Pagamento de fatura
        CSV;
    }

    public function test_le_o_cabecalho_do_csv(): void
    {
        $this->assertSame(
            ['Data', 'Valor', 'Identificador', 'Descrição'],
            $this->service->cabecalho($this->csvNubank())
        );
    }

    public function test_assinatura_e_estavel_pro_mesmo_cabecalho(): void
    {
        $assinatura1 = $this->service->assinaturaColunas($this->csvNubank());
        $assinatura2 = $this->service->assinaturaColunas($this->csvNubank());

        $this->assertSame($assinatura1, $assinatura2);
    }

    public function test_assinatura_muda_se_o_cabecalho_muda(): void
    {
        $outroCsv = "Date,Amount,Id,Description\n01/08/2026,-13.00,abc,teste";

        $this->assertNotSame(
            $this->service->assinaturaColunas($this->csvNubank()),
            $this->service->assinaturaColunas($outroCsv)
        );
    }

    public function test_parseia_data_valor_descricao_e_identificador(): void
    {
        $linhas = $this->service->parsear($this->csvNubank(), $this->modeloNubank());

        $this->assertCount(3, $linhas);

        $this->assertSame('2026-08-01', $linhas[0]->data);
        $this->assertSame(-13.0, $linhas[0]->valor);
        $this->assertSame('Transferência enviada pelo Pix - Zenira Lopes Da Silva', $linhas[0]->descricao);
        $this->assertSame('6a6df54a-7616-4c5a-badd-39273b6dada9', $linhas[0]->identificadorExterno);

        $this->assertSame(3419.24, $linhas[1]->valor);
    }

    public function test_indices_sao_sequenciais_a_partir_de_zero(): void
    {
        $linhas = $this->service->parsear($this->csvNubank(), $this->modeloNubank());

        $this->assertSame([0, 1, 2], array_map(fn ($l) => $l->indice, $linhas));
    }

    public function test_aceita_valor_no_padrao_br_com_milhar_e_decimal_com_virgula(): void
    {
        $csv = "Data,Valor,Descrição\n01/08/2026,\"1.234,56\",Salário";
        $modelo = new ModeloImportacao([
            'coluna_data' => 'Data',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => null,
            'formato_data' => 'd/m/Y',
        ]);

        $linhas = $this->service->parsear($csv, $modelo);

        $this->assertSame(1234.56, $linhas[0]->valor);
        $this->assertNull($linhas[0]->identificadorExterno);
    }

    public function test_lanca_excecao_quando_cabecalho_nao_bate_com_o_modelo(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->parsear($this->csvNubank(), new ModeloImportacao([
            'coluna_data' => 'Coluna que não existe',
            'coluna_valor' => 'Valor',
            'coluna_descricao' => 'Descrição',
            'coluna_identificador' => null,
            'formato_data' => 'd/m/Y',
        ]));
    }
}
