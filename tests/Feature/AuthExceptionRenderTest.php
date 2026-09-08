<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Trava a correção de bootstrap/app.php: uma requisição sem
 * `Accept: application/json` numa rota protegida tinha que devolver 401 JSON,
 * não crashar com 500 (RouteNotFoundException por não existir rota "login").
 */
class AuthExceptionRenderTest extends TestCase
{
    public function test_devolve_401_json_mesmo_sem_header_accept(): void
    {
        $response = $this->post('/api/rendas', []);

        $response->assertStatus(401)->assertJson(['message' => 'Não autenticado.']);
    }
}
