<?php

namespace Tests\Feature;

use App\Models\Renda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RendaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_rendas_do_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        Renda::factory()->count(2)->for($usuario, 'usuario')->create();
        Renda::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/rendas');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_index_exige_autenticacao(): void
    {
        $this->getJson('/api/rendas')->assertStatus(401);
    }

    public function test_store_cria_renda_para_o_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();

        $payload = [
            'descricao' => 'Freela de design',
            'fonte' => 'Freela',
            'valor' => 500.5,
            'data_recebimento' => '2026-09-01',
            'recorrente' => false,
        ];

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/rendas', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.descricao', 'Freela de design')
            ->assertJsonPath('data.valor', '500.50');

        $this->assertDatabaseHas('rendas', [
            'usuario_id' => $usuario->id,
            'descricao' => 'Freela de design',
        ]);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/rendas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['descricao', 'fonte', 'valor', 'data_recebimento']);
    }

    public function test_show_devolve_404_pra_renda_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $renda = Renda::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->getJson("/api/rendas/{$renda->id}")
            ->assertStatus(404);
    }

    public function test_update_altera_renda_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $renda = Renda::factory()->for($usuario, 'usuario')->create(['descricao' => 'Antiga']);

        $response = $this->actingAs($usuario, 'sanctum')
            ->putJson("/api/rendas/{$renda->id}", ['descricao' => 'Nova descrição']);

        $response->assertStatus(200)->assertJsonPath('data.descricao', 'Nova descrição');
        $this->assertDatabaseHas('rendas', ['id' => $renda->id, 'descricao' => 'Nova descrição']);
    }

    public function test_update_devolve_404_pra_renda_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $renda = Renda::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->putJson("/api/rendas/{$renda->id}", ['descricao' => 'Hack'])
            ->assertStatus(404);
    }

    public function test_destroy_apaga_renda_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $renda = Renda::factory()->for($usuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/rendas/{$renda->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('rendas', ['id' => $renda->id]);
    }

    public function test_destroy_devolve_404_pra_renda_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $renda = Renda::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/rendas/{$renda->id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('rendas', ['id' => $renda->id]);
    }
}
