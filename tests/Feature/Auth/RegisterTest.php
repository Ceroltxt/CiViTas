<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('new user can register successfully and receives token and welcome task', function () {
    $response = $this->postJson(route('api.auth.register'), [
        'nome' => 'Carlos Silva',
        'email' => 'carlos.novo@gmail.com',
        'password' => 'secret-password-123',
        'departamento' => 'Engenharia',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'funcionario'])
        ->assertJsonPath('funcionario.email', 'carlos.novo@gmail.com')
        ->assertJsonPath('funcionario.app_role', 'colaborador');

    $funcionario = Funcionario::query()->where('email', 'carlos.novo@gmail.com')->first();
    expect($funcionario)->not()->toBeNull();

    // Verificação da tarefa de boas-vindas criada automaticamente
    $welcomeTask = Tarefa::query()->where('matricula_gestor', $funcionario->matricula_funcionario)->first();
    expect($welcomeTask)->not()->toBeNull();
    expect($welcomeTask->nome)->toContain('Boas-vindas');
});

test('registration rejects duplicate email', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Ana Silva',
        'email' => 'ana@civitas.test',
        'password' => 'secret-password-123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
});

test('registration rejects non-existent email domains without MX records', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Dominio Falso',
        'email' => 'usuario@naoexiste123456789dominiodefato.com',
        'password' => 'secret-password-123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
});

test('registration rejects disposable email domains', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Email Descartavel',
        'email' => 'teste@mailinator.com',
        'password' => 'secret-password-123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['email']);
});

test('registration requires valid password length', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Teste Pequeno',
        'email' => 'pequeno@gmail.com',
        'password' => '123',
    ])->assertStatus(422)
      ->assertJsonValidationErrors(['password']);
});
