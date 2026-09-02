<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObrigacaoFixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'descricao' => ['sometimes', 'required', 'string', 'max:255'],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'dia_vencimento' => ['sometimes', 'required', 'integer', 'between:1,31'],
            'ativa' => ['sometimes', 'boolean'],
        ];
    }
}
