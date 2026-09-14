<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidEmailDomainRule implements ValidationRule
{
    /**
     * Lista de domínios descartáveis, temporários ou fictícios bloqueados.
     *
     * @var list<string>
     */
    protected const DISPOSABLE_DOMAINS = [
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

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || empty($value)) {
            $fail('Informe um endereço de e-mail válido.');

            return;
        }

        $domain = strtolower(substr(strrchr($value, '@') ?: '', 1));

        if (empty($domain) || in_array($domain, self::DISPOSABLE_DOMAINS, true)) {
            $fail('Informe um e-mail real e válido (domínios genéricos ou descartáveis não são aceitos).');

            return;
        }

        // Em testes automatizados, ignora consulta de rede DNS
        if (! app()->runningUnitTests() && ! checkdnsrr($domain, 'MX') && ! checkdnsrr($domain, 'A')) {
            $fail('O domínio do e-mail informado não possui um servidor de e-mail ativo na internet (MX/A inexistente).');
        }
    }
}
