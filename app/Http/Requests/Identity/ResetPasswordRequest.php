<?php

namespace App\Http\Requests\Identity;

use App\Rules\ValidEmailDomainRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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

        if ($this->has('token') && is_string($this->token)) {
            $merge['token'] = trim($this->token);
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
            'email' => ['required', 'string', 'email:rfc', new ValidEmailDomainRule],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'token.required' => 'O token de redefinição é obrigatório.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
