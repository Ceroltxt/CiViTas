<?php

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('domain schema accepts a funcionario with hashed password', function () {
    $departamento = Departamento::query()->create([
        'nome_departamento' => 'Engenharia',
    ]);

    $cargo = Cargo::query()->create([
        'nome_cargo' => 'Desenvolvedor',
    ]);

    $funcionario = Funcionario::query()->create([
        'nome' => 'Ana',
        'sobrenome' => 'Silva',
        'data_nascimento' => '1995-04-12',
        'email' => 'ana@civitas.test',
        'CPF' => '12345678901',
        'pontos_totais' => 10,
        'senha' => 'secret-password',
        'ID_departamento' => $departamento->getKey(),
        'ID_cargo' => $cargo->getKey(),
    ]);

    expect($funcionario->departamento?->nome_departamento)->toBe('Engenharia')
        ->and($funcionario->cargo?->nome_cargo)->toBe('Desenvolvedor')
        ->and($funcionario->senha)->not->toBe('secret-password');
});
