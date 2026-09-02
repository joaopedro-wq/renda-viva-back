<?php

namespace App\Http\Resources;

use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Gasto
 */
class GastoResource extends JsonResource
{
    public static bool $forceWrapping = true;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'descricao' => $this->descricao,
            'valor' => $this->valor,
            'data' => $this->data->toDateString(),
            'categoria_gasto_id' => $this->categoria_gasto_id,
            'obrigacao_fixa_id' => $this->obrigacao_fixa_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
