<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\User;
use Database\Seeders\CategoriaGastoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaGastoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_o_catalogo_global_ordenado_por_nome(): void
    {
        $usuario = User::factory()->create();
        $this->seed(CategoriaGastoSeeder::class);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/categorias-gasto');

        $response->assertStatus(200)->assertJsonCount(CategoriaGasto::count(), 'data');

        $nomes = collect($response->json('data'))->pluck('nome')->all();
        $this->assertSame(collect($nomes)->sort()->values()->all(), $nomes);
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/categorias-gasto')->assertStatus(401);
    }
}
