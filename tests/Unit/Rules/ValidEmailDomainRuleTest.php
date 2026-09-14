<?php

use App\Rules\ValidEmailDomainRule;
use Tests\TestCase;

uses(TestCase::class);

test('email domain rule allows legitimate domains in tests', function () {
    $rule = new ValidEmailDomainRule;
    $validEmails = [
        'usuario@gmail.com',
        'contato@empresa.com.br',
        'dev@civitas.org',
    ];

    foreach ($validEmails as $email) {
        $error = null;
        $rule->validate('email', $email, function ($msg) use (&$error) {
            $error = $msg;
        });
        expect($error)->toBeNull();
    }
});

test('email domain rule blocks disposable domains', function () {
    $rule = new ValidEmailDomainRule;
    $disposableEmails = [
        'teste@mailinator.com',
        'alguem@tempmail.com',
        'fake@10minutemail.com',
        'user@yopmail.com',
        'user@qualquercoisa.com',
    ];

    foreach ($disposableEmails as $email) {
        $error = null;
        $rule->validate('email', $email, function ($msg) use (&$error) {
            $error = $msg;
        });
        expect($error)->toBe('Informe um e-mail real e válido (domínios genéricos ou descartáveis não são aceitos).');
    }
});

test('email domain rule rejects invalid non-email values', function () {
    $rule = new ValidEmailDomainRule;
    $error = null;
    $rule->validate('email', '', function ($msg) use (&$error) {
        $error = $msg;
    });
    expect($error)->not()->toBeNull();
});
