<?php

namespace Tests\Feature;

use App\Models\MovimentoColchao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentoColchaoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_movimentos_do_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        MovimentoColchao::factory()->count(2)->for($usuario, 'usuario')->create();
        MovimentoColchao::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/movimentos-colchao');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_index_filtra_por_mes(): void
    {
        $usuario = User::factory()->create();

        MovimentoColchao::factory()->for($usuario, 'usuario')->create(['data' => '2026-09-10']);
        MovimentoColchao::factory()->for($usuario, 'usuario')->create(['data' => '2026-08-10']);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/movimentos-colchao?mes=2026-09');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/movimentos-colchao')->assertStatus(401);
    }
}
