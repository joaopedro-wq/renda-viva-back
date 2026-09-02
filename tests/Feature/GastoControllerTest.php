<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\ObrigacaoFixa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GastoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_gastos_do_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        Gasto::factory()->count(2)->for($usuario, 'usuario')->create();
        Gasto::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/gastos');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_index_filtra_por_mes(): void
    {
        $usuario = User::factory()->create();

        Gasto::factory()->for($usuario, 'usuario')->create(['data' => '2026-09-10']);
        Gasto::factory()->for($usuario, 'usuario')->create(['data' => '2026-08-10']);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/gastos?mes=2026-09');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_index_exige_autenticacao(): void
    {
        $this->getJson('/api/gastos')->assertStatus(401);
    }

    public function test_store_cria_gasto_com_categoria_global(): void
    {
        $usuario = User::factory()->create();
        $categoria = CategoriaGasto::first() ?? CategoriaGasto::create([
            'nome' => 'Teste', 'icone' => 'utensils', 'cor' => '#000000',
        ]);

        $payload = [
            'descricao' => 'Mercado',
            'valor' => 150.75,
            'data' => '2026-09-01',
            'categoria_gasto_id' => $categoria->id,
        ];

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/gastos', $payload);

        $response->assertStatus(201)->assertJsonPath('data.categoria_gasto_id', $categoria->id);
    }

    public function test_store_rejeita_obrigacao_fixa_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $obrigacaoAlheia = ObrigacaoFixa::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->postJson('/api/gastos', [
            'descricao' => 'Aluguel',
            'valor' => 100,
            'data' => '2026-09-01',
            'obrigacao_fixa_id' => $obrigacaoAlheia->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['obrigacao_fixa_id']);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario, 'sanctum')
            ->postJson('/api/gastos', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['descricao', 'valor', 'data']);
    }

    public function test_show_devolve_gasto_envelopado_em_data(): void
    {
        $usuario = User::factory()->create();
        $gasto = Gasto::factory()->for($usuario, 'usuario')->create(['descricao' => 'Padaria']);

        $this->actingAs($usuario, 'sanctum')
            ->getJson("/api/gastos/{$gasto->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.descricao', 'Padaria');
    }

    public function test_show_devolve_404_pra_gasto_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $gasto = Gasto::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->getJson("/api/gastos/{$gasto->id}")
            ->assertStatus(404);
    }

    public function test_update_altera_gasto_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $gasto = Gasto::factory()->for($usuario, 'usuario')->create(['descricao' => 'Antigo']);

        $response = $this->actingAs($usuario, 'sanctum')
            ->putJson("/api/gastos/{$gasto->id}", ['descricao' => 'Novo']);

        $response->assertStatus(200)->assertJsonPath('data.descricao', 'Novo');
    }

    public function test_destroy_apaga_gasto_do_proprio_usuario(): void
    {
        $usuario = User::factory()->create();
        $gasto = Gasto::factory()->for($usuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/gastos/{$gasto->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('gastos', ['id' => $gasto->id]);
    }

    public function test_destroy_devolve_404_pra_gasto_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();
        $gasto = Gasto::factory()->for($outroUsuario, 'usuario')->create();

        $this->actingAs($usuario, 'sanctum')
            ->deleteJson("/api/gastos/{$gasto->id}")
            ->assertStatus(404);
    }
}
