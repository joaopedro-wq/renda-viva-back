<?php

namespace App\Services;

use App\Models\Gasto;
use App\Models\MovimentoColchao;
use App\Models\ObrigacaoFixa;
use App\Models\Renda;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * O número que carrega o produto: "quanto dá pra gastar essa semana".
 *
 * Modo prudente é o único modo por ora — nunca projeta renda futura, só usa o
 * que já entrou (`rendas.data_recebimento` já ocorrido). Colchão nunca entra
 * automaticamente nesse cálculo, é rede de segurança à parte (ver CLAUDE.md).
 */
class SafeToSpendService
{
    public function calcular(User $usuario): SafeToSpendResultado
    {
        $hoje = Carbon::now('America/Sao_Paulo');

        $rendaRecebidaMes = (float) Renda::doUsuario($usuario->id)
            ->whereYear('data_recebimento', $hoje->year)
            ->whereMonth('data_recebimento', $hoje->month)
            ->sum('valor');

        $obrigacoesFixasMes = (float) ObrigacaoFixa::doUsuario($usuario->id)
            ->ativas()
            ->sum('valor');

        $gastosMes = (float) Gasto::doUsuario($usuario->id)
            ->whereYear('data', $hoje->year)
            ->whereMonth('data', $hoje->month)
            ->sum('valor');

        $disponivelMes = $rendaRecebidaMes - $obrigacoesFixasMes - $gastosMes;

        $semanasRestantes = $this->semanasRestantesNoMes($hoje);

        // Nunca sugere gastar um valor negativo — se já estourou o mês, o
        // seguro da semana é zero, não um número negativo.
        $valorSeguroSemana = max($disponivelMes / $semanasRestantes, 0);

        $saldoColchao = (float) MovimentoColchao::doUsuario($usuario->id)->sum('valor');

        return new SafeToSpendResultado(
            rendaRecebidaMes: $this->formatar($rendaRecebidaMes),
            obrigacoesFixasMes: $this->formatar($obrigacoesFixasMes),
            gastosMes: $this->formatar($gastosMes),
            disponivelMes: $this->formatar($disponivelMes),
            semanasRestantes: $semanasRestantes,
            valorSeguroSemana: $this->formatar($valorSeguroSemana),
            saldoColchao: $this->formatar($saldoColchao),
        );
    }

    /**
     * Conta quantas semanas (segunda a domingo) ainda tocam o mês corrente, a
     * partir da semana de hoje — a semana atual conta inteira mesmo que
     * parcial, e o loop nunca ultrapassa o fim do mês.
     */
    private function semanasRestantesNoMes(CarbonInterface $hoje): int
    {
        $fimDoMes = $hoje->copy()->endOfMonth()->startOfDay();
        $cursor = $hoje->copy()->startOfWeek(CarbonInterface::MONDAY);

        $semanas = 0;

        while ($cursor->lte($fimDoMes)) {
            $semanas++;
            $cursor->addWeek();
        }

        return max($semanas, 1);
    }

    private function formatar(float $valor): string
    {
        return number_format($valor, 2, '.', '');
    }
}
