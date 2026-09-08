<?php

namespace App\Services;

use App\Models\CategoriaGasto;
use Illuminate\Support\Collection;

/**
 * Sugere como cada linha do extrato deveria virar lançamento — nunca decide
 * sozinho, é sempre uma sugestão que o usuário confirma/edita na revisão.
 * Puro: não consulta banco além do que recebe pronto, não grava nada.
 *
 * Regras travadas com o usuário (ver PROXIMAS-FEATURES.md, Fase 8):
 * - Todo valor positivo é candidato a `renda` por padrão — inclusive Pix de
 *   pessoa física. Prioriza não perder renda real sobre poluir menos o
 *   cálculo; o usuário descarta manualmente o que não for renda de verdade.
 * - Valor negativo é candidato a `gasto`, com categoria sugerida por
 *   palavra-chave — sem categoria quando nada bate (ex.: "Pagamento de
 *   fatura", "Débito em conta" viram gasto avulso sem categoria, de propósito).
 * - Par "lavado" (mesmo identificador externo em duas linhas com valores que
 *   se cancelam) tem a perna positiva marcada pra ignorar — sem isso vira
 *   renda fantasma duplicando um gasto real.
 * - Categoria de renda é "aprendida": se esse usuário já categorizou uma
 *   renda com a mesma descrição do extrato antes (o mesmo pagador tende a
 *   repetir a descrição todo mês), a sugestão vem dali — sem heurística de
 *   palavra-chave, porque não dá pra adivinhar "isso é salário" só pelo texto.
 */
class ClassificacaoImportService
{
    /**
     * "MERCADO" sozinho fica de fora de propósito — bate em intermediários de
     * pagamento como "MERCADO PAGO"/"MERCADO LIVRE", que não são compra de
     * supermercado. "SUPERMERCADO" cobre o caso real sem esse falso positivo
     * (confirmado testando contra um extrato real do Nubank).
     */
    private const PALAVRAS_CHAVE_POR_CATEGORIA = [
        'Alimentação' => ['IFOOD', 'SUPERMERCADO', 'RESTAURANTE', 'PADARIA', 'AÇOUGUE', 'CACAU SHOW'],
        'Transporte' => ['UBER', '99APP', 'POSTO ', 'COMBUSTIVEL', 'COMBUSTÍVEL'],
        'Lazer' => ['CINEMARK', 'CINEMA', 'NETFLIX', 'SPOTIFY', 'PARK'],
        'Saúde' => ['FARMACIA', 'FARMÁCIA', 'DROGARIA', 'HOSPITAL'],
        'Família e pets' => ['PET CENTER', 'PETSHOP', 'PET SHOP'],
    ];

    /**
     * @param  array<int, LinhaExtrato>  $linhas
     * @param  array<int, string>  $origensExternasJaImportadas  `origem_externa_id` já usados por esse usuário
     * @param  Collection<int, CategoriaGasto>  $categoriasGasto
     * @param  array<string, int>  $categoriaRendaPorDescricao  descrição do extrato → `categoria_renda_id` já usada por esse usuário pra essa mesma descrição antes (o aprendizado do import)
     * @return array<int, LinhaClassificada>
     */
    public function classificar(
        array $linhas,
        array $origensExternasJaImportadas,
        Collection $categoriasGasto,
        array $categoriaRendaPorDescricao = []
    ): array {
        $indicesParesLavados = $this->detectarParesLavados($linhas);

        return array_map(
            fn (LinhaExtrato $linha) => $this->classificarLinha(
                $linha,
                $origensExternasJaImportadas,
                $categoriasGasto,
                $categoriaRendaPorDescricao,
                $indicesParesLavados
            ),
            $linhas
        );
    }

    private function classificarLinha(
        LinhaExtrato $linha,
        array $origensExternasJaImportadas,
        Collection $categoriasGasto,
        array $categoriaRendaPorDescricao,
        array $indicesParesLavados
    ): LinhaClassificada {
        $jaImportado = $linha->identificadorExterno !== null
            && in_array($linha->identificadorExterno, $origensExternasJaImportadas, true);

        if (in_array($linha->indice, $indicesParesLavados, true)) {
            return new LinhaClassificada($linha, 'ignorar', null, $jaImportado, 'par_lavado');
        }

        if ($jaImportado) {
            return new LinhaClassificada($linha, 'ignorar', null, true, 'ja_importado');
        }

        if ($linha->valor > 0) {
            $categoriaRendaId = $categoriaRendaPorDescricao[$linha->descricao] ?? null;

            return new LinhaClassificada($linha, 'renda', $categoriaRendaId, false, null);
        }

        return new LinhaClassificada(
            $linha,
            'gasto',
            $this->sugerirCategoriaGasto($linha->descricao, $categoriasGasto),
            false,
            null
        );
    }

    /**
     * Um par "lavado": mesmo identificador externo em duas linhas cujos
     * valores se cancelam (ex.: Nubank credita pra Pix no crédito, depois
     * debita o mesmo valor no destino real). Marca a perna positiva (entrada)
     * pra ignorar — a negativa segue como gasto normal.
     *
     * @param  array<int, LinhaExtrato>  $linhas
     * @return array<int, int> índices a ignorar
     */
    private function detectarParesLavados(array $linhas): array
    {
        $indicesPorIdentificador = [];

        foreach ($linhas as $linha) {
            if ($linha->identificadorExterno === null) {
                continue;
            }

            $indicesPorIdentificador[$linha->identificadorExterno][] = $linha->indice;
        }

        $ignorar = [];

        foreach ($indicesPorIdentificador as $indices) {
            if (count($indices) !== 2) {
                continue;
            }

            [$a, $b] = array_map(fn (int $i) => $linhas[$i], $indices);

            if (abs($a->valor + $b->valor) < 0.01) {
                $ignorar[] = $a->valor > 0 ? $a->indice : $b->indice;
            }
        }

        return $ignorar;
    }

    private function sugerirCategoriaGasto(string $descricao, Collection $categoriasGasto): ?int
    {
        $descricaoNormalizada = mb_strtoupper($descricao);

        foreach (self::PALAVRAS_CHAVE_POR_CATEGORIA as $nomeCategoria => $palavrasChave) {
            foreach ($palavrasChave as $palavraChave) {
                if (str_contains($descricaoNormalizada, $palavraChave)) {
                    /** @var CategoriaGasto|null $categoria */
                    $categoria = $categoriasGasto->firstWhere('nome', $nomeCategoria);

                    return $categoria?->id;
                }
            }
        }

        return null;
    }
}
