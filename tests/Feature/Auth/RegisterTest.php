<?php

use App\Mail\Identity\WelcomeMail;
use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('new user can register successfully with valid cpf, receives welcome task and welcome email', function () {
    Mail::fake();

    $response = $this->postJson(route('api.auth.register'), [
        'nome' => 'Carlos Silva',
        'email' => 'carlos.novo@gmail.com',
        'password' => 'secret-password-123',
        'departamento' => 'Engenharia',
        'CPF' => '111.444.777-35',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['message', 'funcionario'])
        ->assertJsonPath('funcionario.email', 'carlos.novo@gmail.com')
        ->assertJsonPath('funcionario.app_role', 'colaborador')
        ->assertJsonPath('funcionario.cpf', '11144477735');

    $funcionario = Funcionario::query()->where('email', 'carlos.novo@gmail.com')->first();
    expect($funcionario)->not()->toBeNull();
    expect($funcionario->CPF)->toBe('11144477735');

    // Verificação da tarefa de boas-vindas criada automaticamente
    $welcomeTask = Tarefa::query()->where('matricula_gestor', $funcionario->matricula_funcionario)->first();
    expect($welcomeTask)->not()->toBeNull();
    expect($welcomeTask->nome)->toContain('Boas-vindas');

    // Verificação do envio do e-mail de boas-vindas
    Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) use ($funcionario) {
        return $mail->hasTo('carlos.novo@gmail.com') && $mail->funcionario->matricula_funcionario === $funcionario->matricula_funcionario;
    });
});

test('registration rejects invalid cpf check digits', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Carlos Silva',
        'email' => 'carlos.invalido@gmail.com',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-36',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['CPF']);
});

test('registration rejects duplicate cpf', function () {
    // Primeiro cadastro com CPF válido
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Primeiro Usuario',
        'email' => 'primeiro@gmail.com',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-35',
    ])->assertCreated();

    // Tentativa de segundo cadastro com o mesmo CPF
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Segundo Usuario',
        'email' => 'segundo@gmail.com',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-35',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['CPF']);
});

test('registration rejects duplicate email', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Ana Silva',
        'email' => 'ana@civitas.test',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-35',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration rejects non-existent email domains without MX records', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Dominio Falso',
        'email' => 'usuario@naoexiste123456789dominiodefato.com',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-35',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration rejects disposable email domains', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Email Descartavel',
        'email' => 'teste@mailinator.com',
        'password' => 'secret-password-123',
        'CPF' => '111.444.777-35',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registration requires valid password length', function () {
    $this->postJson(route('api.auth.register'), [
        'nome' => 'Teste Pequeno',
        'email' => 'pequeno@gmail.com',
        'password' => '123',
        'CPF' => '111.444.777-35',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
