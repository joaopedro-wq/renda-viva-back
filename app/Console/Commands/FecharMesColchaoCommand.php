<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CushionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Fechamento mensal automático do colchão — roda pra todo usuário (ver
 * CushionService pra regra completa). Agendado em routes/console.php.
 */
class FecharMesColchaoCommand extends Command
{
    protected $signature = 'colchao:fechar-mes {--mes= : Mês a fechar no formato YYYY-MM, padrão é o mês anterior ao atual}';

    protected $description = 'Fecha o mês do colchão pra todos os usuários (aporte/saque automático)';

    public function handle(CushionService $cushionService): int
    {
        $mesFechado = $this->option('mes')
            ? Carbon::createFromFormat('Y-m-d', $this->option('mes').'-01', 'America/Sao_Paulo')
            : Carbon::now('America/Sao_Paulo')->subMonthNoOverflow();

        $this->info("Fechando colchão de {$mesFechado->format('m/Y')}...");

        $totalUsuarios = 0;
        $totalMovimentos = 0;

        User::query()->chunkById(100, function ($usuarios) use ($cushionService, $mesFechado, &$totalUsuarios, &$totalMovimentos) {
            foreach ($usuarios as $usuario) {
                $totalUsuarios++;

                if ($cushionService->fecharMes($usuario, $mesFechado)) {
                    $totalMovimentos++;
                }
            }
        });

        $this->info("Concluído: {$totalUsuarios} usuário(s) processado(s), {$totalMovimentos} movimento(s) de colchão criado(s).");

        return self::SUCCESS;
    }
}
