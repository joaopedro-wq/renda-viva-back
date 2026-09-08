<?php

namespace App\Services;

use App\Models\ModeloImportacao;
use RuntimeException;

/**
 * Lê um CSV de extrato bancário cru e devolve linhas normalizadas, usando o
 * mapeamento de colunas de um `ModeloImportacao`. Não sabe nada sobre
 * renda/gasto/categoria — isso é `ClassificacaoImportService`.
 */
class ExtratoParserService
{
    /**
     * @return array<int, string> cabeçalho do CSV, na ordem original — usado
     *                            pra tela de mapeamento e pra assinatura.
     */
    public function cabecalho(string $conteudoCsv): array
    {
        $handle = $this->stream($conteudoCsv);
        $cabecalho = fgetcsv($handle);
        fclose($handle);

        return array_map('trim', $cabecalho ?: []);
    }

    /** Assinatura estável do cabeçalho — reconhece o mesmo formato de banco de novo. */
    public function assinaturaColunas(string $conteudoCsv): string
    {
        return md5(implode('|', $this->cabecalho($conteudoCsv)));
    }

    /**
     * @return array<int, LinhaExtrato>
     */
    public function parsear(string $conteudoCsv, ModeloImportacao $modelo): array
    {
        $handle = $this->stream($conteudoCsv);
        $cabecalho = array_map('trim', fgetcsv($handle) ?: []);

        $indiceData = array_search($modelo->coluna_data, $cabecalho, true);
        $indiceValor = array_search($modelo->coluna_valor, $cabecalho, true);
        $indiceDescricao = array_search($modelo->coluna_descricao, $cabecalho, true);
        $indiceIdentificador = $modelo->coluna_identificador !== null
            ? array_search($modelo->coluna_identificador, $cabecalho, true)
            : false;

        if ($indiceData === false || $indiceValor === false || $indiceDescricao === false) {
            fclose($handle);

            throw new RuntimeException('O cabeçalho do CSV não bate com o modelo de importação selecionado.');
        }

        $linhas = [];
        $indice = 0;

        while (($linha = fgetcsv($handle)) !== false) {
            if ($linha === [null] || $linha === ['']) {
                continue; // linha em branco no fim do arquivo
            }

            $data = \DateTime::createFromFormat($modelo->formato_data, trim($linha[$indiceData] ?? ''));

            $linhas[] = new LinhaExtrato(
                indice: $indice,
                data: $data ? $data->format('Y-m-d') : null,
                valor: $this->parsearValor($linha[$indiceValor] ?? '0'),
                descricao: trim($linha[$indiceDescricao] ?? ''),
                identificadorExterno: $indiceIdentificador !== false
                    ? (trim($linha[$indiceIdentificador] ?? '') ?: null)
                    : null,
            );

            $indice++;
        }

        fclose($handle);

        return $linhas;
    }

    /** Aceita `1234.56` (Nubank) e `1.234,56` (padrão BR de outros bancos). */
    private function parsearValor(string $bruto): float
    {
        $bruto = trim($bruto);

        if (str_contains($bruto, ',') && str_contains($bruto, '.')) {
            $bruto = str_replace('.', '', $bruto);
        }

        return (float) str_replace(',', '.', $bruto);
    }

    /** @return resource */
    private function stream(string $conteudo)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $conteudo);
        rewind($handle);

        return $handle;
    }
}
