<?php

namespace Tests\Unit;

use App\Models\MovimentoColchao;
use App\Models\Renda;
use App\Models\User;
use App\Services\CushionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CushionServiceTest extends TestCase
{
    use RefreshDatabase;

    private CushionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CushionService;
    }

    private function criarRenda(User $usuario, string $mes, float $valor): void
    {
        Renda::factory()->for($usuario, 'usuario')->create([
            'valor' => $valor,
            'data_recebimento' => "{$mes}-10",
        ]);
    }

    public function test_sem_historico_nos_3_meses_anteriores_nao_faz_nada(): void
    {
        $usuario = User::factory()->create();
        $this->criarRenda($usuario, '2026-09', 3000); // mês fechado, sem histórico antes

        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNull($movimento);
        $this->assertDatabaseCount('movimentos_colchao', 0);
    }

    public function test_renda_acima_da_linha_de_base_gera_aporte_de_20_por_cento_do_excedente(): void
    {
        $usuario = User::factory()->create();

        // Linha de base: média de jun/jul/ago = (2000+2000+2000)/3 = 2000.
        $this->criarRenda($usuario, '2026-06', 2000);
        $this->criarRenda($usuario, '2026-07', 2000);
        $this->criarRenda($usuario, '2026-08', 2000);
        // Mês fechado (set): 3000 — excedente de 1000, aporte de 200.
        $this->criarRenda($usuario, '2026-09', 3000);

        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNotNull($movimento);
        $this->assertSame('aporte_automatico', $movimento->tipo);
        $this->assertEquals(200.00, (float) $movimento->valor);
    }

    public function test_renda_abaixo_da_linha_de_base_gera_saque_de_20_por_cento_do_deficit(): void
    {
        $usuario = User::factory()->create();

        $this->criarRenda($usuario, '2026-06', 2000);
        $this->criarRenda($usuario, '2026-07', 2000);
        $this->criarRenda($usuario, '2026-08', 2000);
        // Mês fechado (set): 1000 — déficit de 1000, saque de 200.
        $this->criarRenda($usuario, '2026-09', 1000);

        MovimentoColchao::factory()->for($usuario, 'usuario')->create([
            'valor' => 1000,
            'tipo' => 'aporte_manual',
        ]);

        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNotNull($movimento);
        $this->assertSame('saque_automatico', $movimento->tipo);
        $this->assertEquals(-200.00, (float) $movimento->valor);
    }

    public function test_saque_nunca_deixa_saldo_negativo(): void
    {
        $usuario = User::factory()->create();

        $this->criarRenda($usuario, '2026-06', 2000);
        $this->criarRenda($usuario, '2026-07', 2000);
        $this->criarRenda($usuario, '2026-08', 2000);
        // Déficit enorme, mas o colchão só tem 50.
        $this->criarRenda($usuario, '2026-09', 0);

        MovimentoColchao::factory()->for($usuario, 'usuario')->create([
            'valor' => 50,
            'tipo' => 'aporte_manual',
        ]);

        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNotNull($movimento);
        $this->assertEquals(-50.00, (float) $movimento->valor);

        $saldoFinal = MovimentoColchao::doUsuario($usuario->id)->sum('valor');
        $this->assertEquals(0.00, (float) $saldoFinal);
    }

    public function test_saldo_ja_zerado_nao_gera_saque(): void
    {
        $usuario = User::factory()->create();

        $this->criarRenda($usuario, '2026-06', 2000);
        $this->criarRenda($usuario, '2026-07', 2000);
        $this->criarRenda($usuario, '2026-08', 2000);
        $this->criarRenda($usuario, '2026-09', 0);

        // Sem nenhum movimento prévio — saldo já é zero.
        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNull($movimento);
        $this->assertDatabaseCount('movimentos_colchao', 0);
    }

    public function test_renda_igual_a_linha_de_base_nao_gera_movimento(): void
    {
        $usuario = User::factory()->create();

        $this->criarRenda($usuario, '2026-06', 2000);
        $this->criarRenda($usuario, '2026-07', 2000);
        $this->criarRenda($usuario, '2026-08', 2000);
        $this->criarRenda($usuario, '2026-09', 2000);

        $movimento = $this->service->fecharMes($usuario, Carbon::create(2026, 9, 15, 0, 0, 0, 'America/Sao_Paulo'));

        $this->assertNull($movimento);
    }
}
