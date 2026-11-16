<?php

namespace App\Http\Requests\Identity;

use App\Rules\CpfRule;
use App\Rules\ValidEmailDomainRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('email') && is_string($this->email)) {
            $merge['email'] = mb_strtolower(trim($this->email));
        }

        if ($this->has('nome') && is_string($this->nome)) {
            $merge['nome'] = trim($this->nome);
        }

        if ($this->has('sobrenome') && is_string($this->sobrenome)) {
            $merge['sobrenome'] = trim($this->sobrenome);
        }

        if ($this->has('CPF') && is_string($this->CPF)) {
            $merge['CPF'] = CpfRule::sanitize($this->CPF);
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'sobrenome' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'unique:funcionario,email',
                new ValidEmailDomainRule(),
            ],
            'password' => ['required', 'string', 'min:8'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'CPF' => [
                'nullable',
                'string',
                new CpfRule(),
                Rule::unique('funcionario', 'CPF'),
            ],
        ];
    }
}