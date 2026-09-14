<?php

use App\Rules\CpfRule;

test('cpf rule passes with valid unmasked and masked cpf', function () {
    $rule = new CpfRule;

    $error = null;
    $rule->validate('cpf', '11144477735', function ($msg) use (&$error) {
        $error = $msg;
    });
    expect($error)->toBeNull();

    $error = null;
    $rule->validate('cpf', '111.444.777-35', function ($msg) use (&$error) {
        $error = $msg;
    });
    expect($error)->toBeNull();
});

test('cpf rule fails with wrong check digits', function () {
    $rule = new CpfRule;

    $error = null;
    $rule->validate('cpf', '11144477736', function ($msg) use (&$error) {
        $error = $msg;
    });
    expect($error)->toBe('O CPF informado é inválido.');
});

test('cpf rule fails with repeated digits sequence', function () {
    $rule = new CpfRule;
    $repeatedCpfs = [
        '000.000.000-00',
        '111.111.111-11',
        '99999999999',
    ];

    foreach ($repeatedCpfs as $cpf) {
        $error = null;
        $rule->validate('cpf', $cpf, function ($msg) use (&$error) {
            $error = $msg;
        });
        expect($error)->toBe('O CPF informado é inválido.');
    }
});

test('cpf rule fails with incorrect length or non-digits', function () {
    $rule = new CpfRule;
    $invalidCpfs = [
        '123',
        'abcdefghijk',
        '123456789012',
    ];

    foreach ($invalidCpfs as $cpf) {
        $error = null;
        $rule->validate('cpf', $cpf, function ($msg) use (&$error) {
            $error = $msg;
        });
        expect($error)->not()->toBeNull();
    }
});
