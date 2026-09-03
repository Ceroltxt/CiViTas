<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
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
            'nome' => ['sometimes', 'required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'prioridade' => ['sometimes', 'required', 'string', 'in:alta,media,baixa'],
            'data_inicio' => ['nullable', 'date'],
            'data_previsao_fim' => ['nullable', 'date'],
            'ativo' => ['nullable', 'boolean'],
        ];
    }
}
