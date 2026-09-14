<?php

namespace App\Http\Requests\Identity;

use App\Rules\CpfRule;
use App\Rules\ValidEmailDomainRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara e normaliza os dados antes da validação.
     */
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

        if ($this->has('CPF') && (is_string($this->CPF) || is_numeric($this->CPF))) {
            $merge['CPF'] = CpfRule::sanitize((string) $this->CPF);
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'sobrenome' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:funcionario,email',
                new ValidEmailDomainRule,
            ],
            'password' => ['required', 'string', 'min:8'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'CPF' => [
                'required',
                'string',
                new CpfRule,
                'unique:funcionario,CPF',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome completo é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail real e válido.',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'CPF.required' => 'O CPF é obrigatório.',
            'CPF.unique' => 'Este CPF já está cadastrado no sistema.',
        ];
    }
}
