<?php

use App\Domain\Authorization\AppProfile;
use App\Domain\Authorization\Permissions;
use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Project\Projeto;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\DomainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AuthorizationSeeder::class);
    $this->seed(DomainSeeder::class);
});

function funcionarioByEmail(string $email): Funcionario
{
    return Funcionario::query()->where('email', $email)->firstOrFail();
}

test('operational cargo names like RH are colaborador profile without assign', function () {
    $rhCargo = Cargo::query()->firstOrCreate(['nome_cargo' => 'RH']);

    $funcionario = Funcionario::query()->create([
        'nome' => 'Maria',
        'sobrenome' => 'Souza',
        'data_nascimento' => '1992-01-01',
        'email' => 'rh@civitas.test',
        'CPF' => '55544433322',
        'pontos_totais' => 0,
        'senha' => 'secret-password',
        'ID_cargo' => $rhCargo->ID_cargo,
    ]);

    expect($funcionario->profile())->toBe(AppProfile::Colaborador)
        ->and($funcionario->hasPermission(Permissions::TASKS_VIEW))->toBeTrue()
        ->and($funcionario->hasPermission(Permissions::TASKS_ASSIGN))->toBeFalse();
});

test('substring admin in cargo name does not grant admin profile', function () {
    $cargo = Cargo::query()->firstOrCreate(['nome_cargo' => 'Administração de Contratos']);

    $funcionario = Funcionario::query()->create([
        'nome' => 'João',
        'sobrenome' => 'Silva',
        'data_nascimento' => '1990-01-01',
        'email' => 'contratos@civitas.test',
        'CPF' => '44433322211',
        'pontos_totais' => 0,
        'senha' => 'secret-password',
        'ID_cargo' => $cargo->ID_cargo,
    ]);

    expect($funcionario->profile())->toBe(AppProfile::Colaborador);
});

test('colaborador receives colaborador permission set', function () {
    $colaborador = funcionarioByEmail('ana@civitas.test');

    expect($colaborador->hasPermission(Permissions::TASKS_VIEW))->toBeTrue()
        ->and($colaborador->hasPermission(Permissions::TASKS_ASSIGN))->toBeFalse()
        ->and($colaborador->hasPermission(Permissions::ACCESS_VIEW))->toBeFalse();
});

test('gestor can assign tasks but not manage global access', function () {
    $gestor = funcionarioByEmail('gestor@civitas.test');

    expect($gestor->hasPermission(Permissions::TASKS_ASSIGN))->toBeTrue()
        ->and($gestor->hasPermission(Permissions::ACCESS_MANAGE))->toBeFalse();
});

test('admin has all permissions', function () {
    $admin = funcionarioByEmail('admin@civitas.test');

    foreach (Permissions::all() as $slug) {
        expect($admin->hasPermission($slug))->toBeTrue();
    }
});

test('colaborador cannot assign tasks via policy', function () {
    $colaborador = funcionarioByEmail('ana@civitas.test');
    $tarefa = Tarefa::query()->firstOrFail();

    expect(Gate::forUser($colaborador)->allows('assign', $tarefa))->toBeFalse();
});

test('gestor can assign tasks in managed project', function () {
    $gestor = funcionarioByEmail('gestor@civitas.test');
    $projeto = Projeto::query()->where('nome', 'Nova Praça Central')->firstOrFail();
    $tarefa = Tarefa::query()->where('ID_projeto', $projeto->ID_projeto)->firstOrFail();

    expect(Gate::forUser($gestor)->allows('assign', $tarefa))->toBeTrue();
});

test('colaborador can update assigned task but gestor from other project cannot', function () {
    $colaborador = funcionarioByEmail('ana@civitas.test');
    $gestor = funcionarioByEmail('gestor@civitas.test');
    $tarefa = Tarefa::query()->whereHas('colaboradores', fn ($q) => $q->where('email', 'ana@civitas.test'))->firstOrFail();

    expect(Gate::forUser($colaborador)->allows('update', $tarefa))->toBeTrue();

    $otherGestor = Funcionario::query()->create([
        'nome' => 'Outro',
        'sobrenome' => 'Gestor',
        'data_nascimento' => '1990-01-01',
        'email' => 'outro-gestor@civitas.test',
        'CPF' => '99988877766',
        'pontos_totais' => 0,
        'senha' => 'secret-password',
        'ID_cargo' => $gestor->ID_cargo,
    ]);

    expect(Gate::forUser($otherGestor)->allows('update', $tarefa))->toBeFalse();
});

test('admin can view any funcionario access list', function () {
    $admin = funcionarioByEmail('admin@civitas.test');

    expect(Gate::forUser($admin)->allows('viewAny', Funcionario::class))->toBeTrue();
});

test('colaborador cannot view access list', function () {
    $colaborador = funcionarioByEmail('ana@civitas.test');

    expect(Gate::forUser($colaborador)->allows('viewAny', Funcionario::class))->toBeFalse();
});

test('gestor manages own team via equipe policy', function () {
    $gestor = funcionarioByEmail('gestor@civitas.test');
    $equipe = Equipe::query()->where('matricula_gestor', $gestor->matricula_funcionario)->firstOrFail();

    expect(Gate::forUser($gestor)->allows('update', $equipe))->toBeTrue();
});
