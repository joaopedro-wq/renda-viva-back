<?php

namespace App\Services;

/** Uma linha normalizada do extrato, já sem depender do formato de origem. */
final readonly class LinhaExtrato
{
    public function __construct(
        public int $indice,
        public ?string $data,
        public float $valor,
        public string $descricao,
        public ?string $identificadorExterno,
    ) {}
}
