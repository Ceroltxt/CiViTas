<?php

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createFuncionarioForAuth(): Funcionario
{
    $departamento = Departamento::query()->create(['nome_departamento' => 'Engenharia']);
    $cargo = Cargo::query()->create(['nome_cargo' => 'Desenvolvedor']);

    return Funcionario::query()->create([
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
}

test('funcionario can login', function () {
    createFuncionarioForAuth();

    $this->postJson(route('api.auth.login'), [
        'email' => 'ana@civitas.test',
        'password' => 'secret-password',
    ])->assertOk()->assertJsonStructure(['token', 'token_type', 'funcionario']);
});

test('authenticated funcionario can fetch profile', function () {
    Sanctum::actingAs(createFuncionarioForAuth());

    $this->getJson(route('api.auth.me'))
        ->assertOk()
        ->assertJsonPath('data.email', 'ana@civitas.test');
});
