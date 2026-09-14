<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CpfRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        $cpf = self::sanitize((string) $value);

        if ($cpf === null || strlen($cpf) !== 11) {
            $fail('O CPF informado deve conter 11 dígitos.');

            return;
        }

        // Rejeita sequências repetidas como 000.000.000-00, 111.111.111-11, etc.
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        // Validação do 1º dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += ((int) $cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if (((int) $cpf[9]) !== $digito1) {
            $fail('O CPF informado é inválido.');

            return;
        }

        // Validação do 2º dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += ((int) $cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if (((int) $cpf[10]) !== $digito2) {
            $fail('O CPF informado é inválido.');

            return;
        }
    }

    /**
     * Remove caracteres não numéricos retornando apenas os dígitos.
     */
    public static function sanitize(?string $cpf): ?string
    {
        if ($cpf === null) {
            return null;
        }

        return preg_replace('/\D/', '', $cpf);
    }
}
