<?php

namespace Tests\Feature;

use App\Models\ModeloImportacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeloImportacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_apenas_modelos_do_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        ModeloImportacao::factory()->for($usuario, 'usuario')->create();
        ModeloImportacao::factory()->for($outroUsuario, 'usuario')->create();

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/modelos-importacao');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/modelos-importacao')->assertStatus(401);
    }
}
