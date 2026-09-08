<?php

namespace App\Services;

/**
 * Uma linha do extrato + a sugestão da classificação — nada disso é gravado
 * ainda, é o que a tela de revisão mostra pro usuário confirmar/editar.
 */
final readonly class LinhaClassificada
{
    public function __construct(
        public LinhaExtrato $linha,
        /** 'renda' | 'gasto' | 'ignorar'. */
        public string $tipoSugerido,
        /**
         * `categoria_gasto_id` quando `tipoSugerido` é 'gasto', ou
         * `categoria_renda_id` quando é 'renda' — nunca os dois ao mesmo
         * tempo, então um campo só basta.
         */
        public ?int $categoriaSugeridaId,
        /** Já existe um lançamento com esse `origem_externa_id` pra esse usuário. */
        public bool $jaImportado,
        /** Por que a sugestão é essa — null quando é o caminho padrão (sem motivo especial). */
        public ?string $motivo,
    ) {}
}
