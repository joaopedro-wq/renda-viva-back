<?php

namespace Tests\Feature;

use App\Models\ModeloImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Cobre só o comportamento específico do upload em chunks (protocolo Blueimp via
 * `Content-Range`) — a classificação em si (par lavado, categoria aprendida etc.) já está
 * coberta por `ImportacaoControllerTest`, e o chunk final chama exatamente o mesmo
 * `ImportacaoPreVisualizacaoService`.
 */
class ImportacaoUploadChunkControllerTest extends TestCase
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

    /**
     * Envia `$conteudo` em 2 chunks (metade cada) pro endpoint, sob o mesmo nome de arquivo,
     * simulando o protocolo Blueimp (`Content-Range: bytes {inicio}-{fim}/{total}`).
     *
     * @return array{0: \Illuminate\Testing\TestResponse, 1: \Illuminate\Testing\TestResponse} [respostaDoChunk1, respostaDoChunkFinal]
     */
    private function enviarEmDoisChunks(User $usuario, string $conteudo, string $nome = 'extrato.csv', array $camposExtras = []): array
    {
        $total = strlen($conteudo);
        $meio = intdiv($total, 2);

        $chunk1 = substr($conteudo, 0, $meio);
        $chunk2 = substr($conteudo, $meio);

        $resposta1 = $this->actingAs($usuario, 'sanctum')->post(
            '/api/importacoes/upload-chunk',
            ['arquivo' => UploadedFile::fake()->createWithContent($nome, $chunk1)],
            ['Content-Range' => "bytes 0-".($meio - 1)."/{$total}"],
        );

        $resposta2 = $this->actingAs($usuario, 'sanctum')->post(
            '/api/importacoes/upload-chunk',
            ['arquivo' => UploadedFile::fake()->createWithContent($nome, $chunk2), ...$camposExtras],
            ['Content-Range' => "bytes {$meio}-".($total - 1)."/{$total}"],
        );

        return [$resposta1, $resposta2];
    }

    public function test_upload_chunk_reconhece_formato_nubank_conta_automaticamente(): void
    {
        $usuario = User::factory()->create();

        [$resposta1, $resposta2] = $this->enviarEmDoisChunks($usuario, $this->csvNubank());

        $resposta1->assertStatus(200)->assertExactJson(['recebido' => true]);

        $resposta2->assertStatus(200)->assertJsonPath('precisa_mapear', false);
        $this->assertDatabaseHas('modelos_importacao', ['usuario_id' => $usuario->id, 'nome' => 'Nubank Conta']);
        $this->assertCount(4, $resposta2->json('data'));
    }

    public function test_upload_chunk_pede_mapeamento_quando_nao_reconhece_o_cabecalho(): void
    {
        $usuario = User::factory()->create();

        [, $resposta2] = $this->enviarEmDoisChunks($usuario, $this->csvFormatoDesconhecido());

        $resposta2->assertStatus(200)
            ->assertJsonPath('precisa_mapear', true)
            ->assertJsonPath('cabecalho', ['Date', 'Amount', 'Description']);
    }

    public function test_upload_chunk_aceita_mapeamento_manual_no_chunk_final(): void
    {
        $usuario = User::factory()->create();

        [, $resposta2] = $this->enviarEmDoisChunks($usuario, $this->csvFormatoDesconhecido(), camposExtras: [
            'mapeamento' => [
                'nome' => 'Formato genérico',
                'coluna_data' => 'Date',
                'coluna_valor' => 'Amount',
                'coluna_descricao' => 'Description',
                'formato_data' => 'd/m/Y',
            ],
        ]);

        $resposta2->assertStatus(200)->assertJsonPath('precisa_mapear', false);
        $this->assertDatabaseHas('modelos_importacao', ['usuario_id' => $usuario->id, 'nome' => 'Formato genérico']);
    }

    public function test_upload_chunk_exige_autenticacao(): void
    {
        $response = $this->post(
            '/api/importacoes/upload-chunk',
            ['arquivo' => UploadedFile::fake()->createWithContent('extrato.csv', $this->csvNubank())],
            ['Content-Range' => 'bytes 0-9/10'],
        );

        $response->assertStatus(401);
    }

    public function test_upload_chunk_nao_deixa_arquivo_temporario_apos_processar(): void
    {
        $usuario = User::factory()->create();

        [, $resposta2] = $this->enviarEmDoisChunks($usuario, $this->csvNubank());
        $resposta2->assertStatus(200);

        $modelo = ModeloImportacao::where('usuario_id', $usuario->id)->firstOrFail();
        $pastaChunks = storage_path('app/chunks');

        // Nenhum .part (chunk bruto) nem arquivo reagrupado deve sobrar depois do processamento.
        $restantes = is_dir($pastaChunks) ? glob($pastaChunks.'/*') : [];
        $this->assertSame([], array_values(array_filter($restantes, 'is_file')));
    }
}
