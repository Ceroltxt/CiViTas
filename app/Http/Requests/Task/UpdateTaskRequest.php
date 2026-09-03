<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'data_prazo' => ['sometimes', 'required', 'date'],
            'ID_projeto' => ['nullable', 'integer', 'exists:projeto,ID_projeto'],
            'ID_equipe' => ['nullable', 'integer', 'exists:equipe,ID_equipe'],
            'matricula_colaborador' => ['nullable'],
        ];
    }
}
