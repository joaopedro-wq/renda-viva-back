<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRendaRequest extends FormRequest
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
            'fonte' => ['sometimes', 'required', 'string', 'max:255'],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'data_recebimento' => ['sometimes', 'required', 'date'],
            'recorrente' => ['sometimes', 'boolean'],
        ];
    }
}
