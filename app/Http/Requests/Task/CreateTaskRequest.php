<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'prioridade' => ['required', 'string', 'in:alta,media,baixa'],
            'data_prazo' => ['required', 'date', 'after_or_equal:today'],
            'ID_projeto' => ['nullable', 'integer', 'exists:projeto,ID_projeto'],
            'ID_equipe' => ['nullable', 'integer', 'exists:equipe,ID_equipe'],
            'matricula_colaborador' => ['nullable'],
            'pessoal' => ['nullable', 'boolean'],
            'subtarefas' => ['nullable', 'array'],
            'subtarefas.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O título da tarefa é obrigatório.',
            'prioridade.required' => 'A prioridade da tarefa é obrigatória.',
            'prioridade.in' => 'A prioridade deve ser alta, media ou baixa.',
            'data_prazo.required' => 'O prazo final da tarefa é obrigatório.',
            'data_prazo.after_or_equal' => 'A data do prazo deve ser hoje ou uma data futura.',
        ];
    }
}
