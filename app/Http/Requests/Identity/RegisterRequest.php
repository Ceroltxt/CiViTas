<?php

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:100'],
            'sobrenome' => ['nullable', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:funcionario,email',
                function ($attribute, $value, $fail) {
                    $invalidDomains = [
                        'mailinator.com',
                        'tempmail.com',
                        '10minutemail.com',
                        'guerrillamail.com',
                        'yopmail.com',
                        'trashmail.com',
                        'dispostable.com',
                        'sharklasers.com',
                        'test.com',
                        'teste.com',
                        'example.com',
                        'fake.com',
                        'qualquercoisa.com',
                        'naoexiste.com',
                    ];
                    $domain = strtolower(substr(strrchr((string) $value, "@") ?: '', 1));
                    if (empty($domain) || in_array($domain, $invalidDomains, true)) {
                        $fail('Informe um e-mail real e válido (domínios genéricos ou descartáveis não são aceitos).');
                        return;
                    }

                    if (! app()->runningUnitTests() && ! checkdnsrr($domain, 'MX') && ! checkdnsrr($domain, 'A')) {
                        $fail('O domínio do e-mail informado não possui um servidor de e-mail ativo na internet (MX/A inexistente).');
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'CPF' => ['nullable', 'string', 'max:14'],
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
            'email.email' => 'Informe um endereço de e-mail real e válido com servidor de e-mail existente (domínio/MX ativo).',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
        ];
    }
}
