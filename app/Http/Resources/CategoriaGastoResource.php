<?php

namespace App\Http\Resources;

use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CategoriaGasto
 */
class CategoriaGastoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'icone' => $this->icone,
            'cor' => $this->cor,
        ];
    }
}
