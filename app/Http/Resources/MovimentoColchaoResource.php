<?php

namespace App\Http\Resources;

use App\Models\MovimentoColchao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MovimentoColchao
 */
class MovimentoColchaoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'valor' => $this->valor,
            'tipo' => $this->tipo,
            'descricao' => $this->descricao,
            'data' => $this->data->toDateString(),
        ];
    }
}
