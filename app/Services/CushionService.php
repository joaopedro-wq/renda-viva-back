<?php

namespace App\Services;

use App\Models\MovimentoColchao;
use App\Models\Renda;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;


class CushionService
{
    private const PERCENTUAL_APORTE_SAQUE = 0.20;

    private const MESES_LINHA_DE_BASE = 3;

    public function fecharMes(User $usuario, ?CarbonInterface $mesFechado = null): ?MovimentoColchao
    {
        $mesFechado ??= Carbon::now('America/Sao_Paulo')->subMonthNoOverflow();

        $baseline = $this->linhaDeBase($usuario, $mesFechado);

        // Sem histórico dos 3 meses anteriores — nada pra comparar ainda,
        // não faz sentido aportar/sacar no primeiro mês de uso.
        if ($baseline === null) {
            return null;
        }

        $rendaMesFechado = $this->rendaDoMes($usuario, $mesFechado);
        $diferenca = $rendaMesFechado - $baseline;

        // Centavo de diferença não vale a pena virar movimento no colchão.
        if (abs($diferenca) < 0.01) {
            return null;
        }

        return $diferenca > 0
            ? $this->aportar($usuario, $mesFechado, $diferenca)
            : $this->sacar($usuario, $mesFechado, abs($diferenca));
    }

    private function aportar(User $usuario, CarbonInterface $mesFechado, float $excedente): MovimentoColchao
    {
        $valor = round($excedente * self::PERCENTUAL_APORTE_SAQUE, 2);

        return $this->registrar($usuario, $mesFechado, $valor, 'aporte_automatico');
    }

    private function sacar(User $usuario, CarbonInterface $mesFechado, float $deficit): ?MovimentoColchao
    {
        $saldoAtual = (float) MovimentoColchao::doUsuario($usuario->id)->sum('valor');

        // Nunca deixa o colchão negativo — o saque é limitado ao saldo disponível.
        $valor = min(round($deficit * self::PERCENTUAL_APORTE_SAQUE, 2), max($saldoAtual, 0));

        if ($valor <= 0) {
            return null;
        }

        return $this->registrar($usuario, $mesFechado, -$valor, 'saque_automatico');
    }

    private function registrar(User $usuario, CarbonInterface $mesFechado, float $valor, string $tipo): MovimentoColchao
    {
        return MovimentoColchao::create([
            'usuario_id' => $usuario->id,
            'valor' => $valor,
            'tipo' => $tipo,
            'descricao' => sprintf('Fechamento automático de %s', $mesFechado->translatedFormat('m/Y')),
            'data' => $mesFechado->copy()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Média simples da renda dos MESES_LINHA_DE_BASE meses anteriores ao mês
     * fechado. Devolve null se não houver nenhuma renda lançada nesses meses
     * (usuário novo, sem histórico suficiente).
     */
    private function linhaDeBase(User $usuario, CarbonInterface $mesFechado): ?float
    {
        $temHistorico = false;
        $soma = 0.0;

        for ($i = 1; $i <= self::MESES_LINHA_DE_BASE; $i++) {
            $mes = $mesFechado->copy()->subMonthsNoOverflow($i);
            $totalMes = $this->rendaDoMes($usuario, $mes);

            if ($totalMes > 0) {
                $temHistorico = true;
            }

            $soma += $totalMes;
        }

        if (! $temHistorico) {
            return null;
        }

        return $soma / self::MESES_LINHA_DE_BASE;
    }

    private function rendaDoMes(User $usuario, CarbonInterface $mes): float
    {
        return (float) Renda::doUsuario($usuario->id)
            ->whereYear('data_recebimento', $mes->year)
            ->whereMonth('data_recebimento', $mes->month)
            ->sum('valor');
    }
}
