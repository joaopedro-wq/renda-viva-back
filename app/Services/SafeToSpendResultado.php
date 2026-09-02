<?php

namespace App\Services;

/**
 * Resultado do cálculo de "quanto dá pra gastar essa semana" — payload puro,
 * sem regra de negócio (a regra mora no SafeToSpendService).
 */
final readonly class SafeToSpendResultado
{
    public function __construct(
        public string $rendaRecebidaMes,
        public string $obrigacoesFixasMes,
        public string $gastosMes,
        public string $disponivelMes,
        public int $semanasRestantes,
        public string $valorSeguroSemana,
        public string $saldoColchao,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'renda_recebida_mes' => $this->rendaRecebidaMes,
            'obrigacoes_fixas_mes' => $this->obrigacoesFixasMes,
            'gastos_mes' => $this->gastosMes,
            'disponivel_mes' => $this->disponivelMes,
            'semanas_restantes' => $this->semanasRestantes,
            'valor_seguro_semana' => $this->valorSeguroSemana,
            'saldo_colchao' => $this->saldoColchao,
        ];
    }
}
