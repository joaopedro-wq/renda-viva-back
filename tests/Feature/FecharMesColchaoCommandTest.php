<?php

namespace Tests\Feature;

use App\Models\Renda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FecharMesColchaoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_fecha_o_mes_para_todos_os_usuarios(): void
    {
        $usuarioComExcedente = User::factory()->create();
        foreach (['2026-06', '2026-07', '2026-08'] as $mes) {
            Renda::factory()->for($usuarioComExcedente, 'usuario')->create([
                'valor' => 2000, 'data_recebimento' => "{$mes}-10",
            ]);
        }
        Renda::factory()->for($usuarioComExcedente, 'usuario')->create([
            'valor' => 3000, 'data_recebimento' => '2026-09-10',
        ]);

        // Usuário sem histórico — não deve gerar movimento.
        $usuarioNovo = User::factory()->create();
        Renda::factory()->for($usuarioNovo, 'usuario')->create([
            'valor' => 1000, 'data_recebimento' => '2026-09-10',
        ]);

        $this->artisan('colchao:fechar-mes', ['--mes' => '2026-09'])
            ->expectsOutputToContain('2 usuário(s) processado(s), 1 movimento(s)')
            ->assertExitCode(0);

        $this->assertDatabaseHas('movimentos_colchao', [
            'usuario_id' => $usuarioComExcedente->id,
            'tipo' => 'aporte_automatico',
        ]);
        $this->assertDatabaseMissing('movimentos_colchao', [
            'usuario_id' => $usuarioNovo->id,
        ]);
    }
}
