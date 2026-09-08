<?php

namespace App\Http\Resources;

use App\Models\ModeloImportacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModeloImportacao
 */
class ModeloImportacaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'coluna_data' => $this->coluna_data,
            'coluna_valor' => $this->coluna_valor,
            'coluna_descricao' => $this->coluna_descricao,
            'coluna_identificador' => $this->coluna_identificador,
            'formato_data' => $this->formato_data,
        ];
    }
}
