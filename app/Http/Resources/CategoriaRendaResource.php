<?php

namespace App\Http\Resources;

use App\Models\CategoriaRenda;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CategoriaRenda
 */
class CategoriaRendaResource extends JsonResource
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
