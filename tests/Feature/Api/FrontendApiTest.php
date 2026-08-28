<?php

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use App\Models\Project\Projeto;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use Database\Seeders\DomainSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DomainSeeder::class);
});

function actingAsColaborador(): Funcionario
{
    $funcionario = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();
    Sanctum::actingAs($funcionario);

    return $funcionario;
}

test('frontend me endpoint returns user summary shape', function () {
    actingAsColaborador();

    $this->getJson(route('api.me.summary'))
        ->assertOk()
        ->assertJsonStructure(['id', 'name', 'role'])
        ->assertJsonPath('name', 'Costa Neves')
        ->assertJsonPath('role', 'Colaborador');
});

test('frontend tasks endpoint returns task list', function () {
    actingAsColaborador();

    $this->getJson(route('api.tasks'))
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure([
            '*' => ['id', 'title', 'priority', 'status', 'assignees'],
        ]);
});

test('frontend dashboard metrics endpoint returns metrics', function () {
    actingAsColaborador();

    $this->getJson(route('api.dashboard.metrics'))
        ->assertOk()
        ->assertJsonStructure([
            '*' => ['id', 'label', 'value'],
        ]);
});

test('frontend navigation endpoint returns menu items', function () {
    actingAsColaborador();

    $this->getJson(route('api.navigation'))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Início', 'to' => '/colaborador']);
});

test('frontend ranking endpoint returns ranking entries', function () {
    actingAsColaborador();

    $this->getJson(route('api.dashboard.ranking'))
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure([
            '*' => ['position', 'user', 'stars'],
        ]);
});

test('frontend projects endpoint returns project progress list', function () {
    actingAsColaborador();

    $this->getJson(route('api.dashboard.projects'))
        ->assertOk()
        ->assertJsonIsArray()
        ->assertJsonStructure([
            '*' => ['id', 'name', 'progress', 'color'],
        ]);
});

test('unauthenticated requests to frontend endpoints are rejected', function () {
    $this->getJson(route('api.tasks'))->assertUnauthorized();
});
