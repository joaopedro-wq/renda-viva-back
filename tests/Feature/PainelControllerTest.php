<?php

namespace Tests\Feature;

use App\Models\Gasto;
use App\Models\MovimentoColchao;
use App\Models\ObrigacaoFixa;
use App\Models\Renda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PainelControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/painel/dado-da-semana')->assertStatus(401);
    }

    public function test_calcula_disponivel_e_valor_seguro_por_semana(): void
    {
        // Terça-feira, 15/09/2026 — restam 3 semanas (semana atual + 2) até o
        // fim do mês (30/09, quarta).
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/Sao_Paulo'));

        $usuario = User::factory()->create();

        Renda::factory()->for($usuario, 'usuario')->create([
            'valor' => 3000,
            'data_recebimento' => '2026-09-05',
        ]);
        // Renda de outro mês não deve entrar no cálculo.
        Renda::factory()->for($usuario, 'usuario')->create([
            'valor' => 9999,
            'data_recebimento' => '2026-08-20',
        ]);

        ObrigacaoFixa::factory()->for($usuario, 'usuario')->create(['valor' => 1200, 'ativa' => true]);
        // Obrigação inativa não deve entrar no cálculo.
        ObrigacaoFixa::factory()->for($usuario, 'usuario')->create(['valor' => 500, 'ativa' => false]);

        Gasto::factory()->for($usuario, 'usuario')->create(['valor' => 300, 'data' => '2026-09-10']);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/painel/dado-da-semana');

        $response->assertStatus(200)
            ->assertJsonPath('data.renda_recebida_mes', '3000.00')
            ->assertJsonPath('data.obrigacoes_fixas_mes', '1200.00')
            ->assertJsonPath('data.gastos_mes', '300.00')
            ->assertJsonPath('data.disponivel_mes', '1500.00')
            ->assertJsonPath('data.semanas_restantes', 3)
            ->assertJsonPath('data.valor_seguro_semana', '500.00');
    }

    public function test_nunca_devolve_valor_seguro_negativo(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/Sao_Paulo'));

        $usuario = User::factory()->create();

        Renda::factory()->for($usuario, 'usuario')->create([
            'valor' => 100,
            'data_recebimento' => '2026-09-05',
        ]);
        ObrigacaoFixa::factory()->for($usuario, 'usuario')->create(['valor' => 1000, 'ativa' => true]);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/painel/dado-da-semana');

        $response->assertStatus(200)->assertJsonPath('data.valor_seguro_semana', '0.00');
    }

    public function test_colchao_e_exposto_separado_e_nunca_entra_no_calculo(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/Sao_Paulo'));

        $usuario = User::factory()->create();

        MovimentoColchao::factory()->for($usuario, 'usuario')->create([
            'valor' => 500,
            'tipo' => 'aporte_manual',
        ]);
        MovimentoColchao::factory()->for($usuario, 'usuario')->create([
            'valor' => -100,
            'tipo' => 'saque_manual',
        ]);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/painel/dado-da-semana');

        $response->assertStatus(200)
            ->assertJsonPath('data.saldo_colchao', '400.00')
            ->assertJsonPath('data.disponivel_mes', '0.00');
    }

    public function test_nao_mistura_dado_de_outro_usuario(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/Sao_Paulo'));

        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        Renda::factory()->for($outroUsuario, 'usuario')->create([
            'valor' => 5000,
            'data_recebimento' => '2026-09-05',
        ]);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/painel/dado-da-semana');

        $response->assertStatus(200)->assertJsonPath('data.renda_recebida_mes', '0.00');
    }
}
