<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
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
            'matricula_gestor' => ['sometimes', 'required', 'integer', 'exists:funcionario,matricula_funcionario'],
            'ID_projeto' => ['nullable', 'integer', 'exists:projeto,ID_projeto'],
            'membros' => ['nullable', 'array'],
            'membros.*' => ['integer', 'exists:funcionario,matricula_funcionario'],
        ];
    }
}
