<?php

namespace App\Http\Resources;

use App\Models\Renda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Renda
 */
class RendaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'descricao' => $this->descricao,
            'fonte' => $this->fonte,
            'valor' => $this->valor,
            'data_recebimento' => $this->data_recebimento->toDateString(),
            'recorrente' => $this->recorrente,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
