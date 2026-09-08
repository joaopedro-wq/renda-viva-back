<?php

namespace Tests\Feature;

use App\Models\CategoriaRenda;
use App\Models\User;
use Database\Seeders\CategoriaRendaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaRendaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_o_catalogo_global_ordenado_por_nome(): void
    {
        $usuario = User::factory()->create();
        $this->seed(CategoriaRendaSeeder::class);

        $response = $this->actingAs($usuario, 'sanctum')->getJson('/api/categorias-renda');

        $response->assertStatus(200)->assertJsonCount(CategoriaRenda::count(), 'data');

        $nomes = collect($response->json('data'))->pluck('nome')->all();
        $this->assertSame(collect($nomes)->sort()->values()->all(), $nomes);
    }

    public function test_exige_autenticacao(): void
    {
        $this->getJson('/api/categorias-renda')->assertStatus(401);
    }
}
