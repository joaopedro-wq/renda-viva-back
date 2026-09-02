<?php

namespace Tests\Feature;

use App\Models\ObrigacaoFixa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObrigacaoFixaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_obrigacoes_do_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        ObrigacaoFixa::factory()->count(2)->for($usuario, 'usuario')->create();
        ObrigacaoFixa::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/obrigacoes-fixas');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_index_exige_autenticacao(): void
    {
        $this->getJson('/api/obrigacoes-fixas')->assertStatus(401);
    }

    public function test_store_cria_obrigacao_para_o_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();

        $payload = [
            'descricao' => 'Aluguel',
            'valor' => 1200,
            'dia_vencimento' => 5,
        ];

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/obrigacoes-fixas', $payload);

        $response->assertStatus(201)->assertJsonPath('data.descricao', 'Aluguel');

        $this->assertDatabaseHas('obrigacoes_fixas', [
            'usuario_id' => $usuario->id,
            'descricao' => 'Aluguel',
        ]);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario, 'sanctum')
            ->postJson('/api/obrigacoes-fixas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['descricao', 'valor', 'dia_vencimento']);
    }

    public function test_store_valida_dia_vencimento_no_intervalo(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario, 'sanctum')
            ->postJson('/api/obrigacoes-fixas', [
                'descricao' => 'Aluguel',
                'valor' => 100,
                'dia_vencimento' => 32,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dia_vencimento']);
    }

    public function test_show_devolve_404_pra_obrigacao_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $obrigacao = ObrigacaoFixa::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->getJson("/api/obrigacoes-fixas/{$obrigacao->id}")
            ->assertStatus(404);
    }

    public function test_update_altera_obrigacao_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $obrigacao = ObrigacaoFixa::factory()->for($usuario, 'usuario')->create(['ativa' => true]);

        $response = $this->actingAs($usuario, 'sanctum')
            ->putJson("/api/obrigacoes-fixas/{$obrigacao->id}", ['ativa' => false]);

        $response->assertStatus(200)->assertJsonPath('data.ativa', false);
    }

    public function test_destroy_apaga_obrigacao_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $obrigacao = ObrigacaoFixa::factory()->for($usuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/obrigacoes-fixas/{$obrigacao->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('obrigacoes_fixas', ['id' => $obrigacao->id]);
    }

    public function test_destroy_devolve_404_pra_obrigacao_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $obrigacao = ObrigacaoFixa::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/obrigacoes-fixas/{$obrigacao->id}")
            ->assertStatus(404);
    }
}
