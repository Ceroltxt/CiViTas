<?php

use App\Models\Identity\Funcionario;
use App\Models\Team\Equipe;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsAdmin(): Funcionario
{
    $admin = Funcionario::query()->where('email', 'admin@civitas.test')->firstOrFail();
    Sanctum::actingAs($admin);

    return $admin;
}

test('admin can fetch gestores list for allocation dropdown', function () {
    actingAsAdmin();

    $response = $this->getJson(route('api.gestores'));

    $response->assertOk();
    $gestores = $response->json();
    $list = is_array($gestores) && isset($gestores['data']) ? $gestores['data'] : $gestores;

    expect(count($list))->toBeGreaterThan(0);
});

test('admin can create a new team and allocate a gestor in database', function () {
    actingAsAdmin();

    $gestor = Funcionario::query()->where('email', 'gestor@civitas.test')->firstOrFail();
    $colaborador = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();

    $response = $this->postJson(route('api.teams.store'), [
        'nome' => 'Equipe de Infraestrutura',
        'matricula_gestor' => $gestor->matricula_funcionario,
        'membros' => [$colaborador->matricula_funcionario],
    ]);

    $response->assertCreated();

    $name = $response->json('name') ?? $response->json('data.name');
    expect($name)->toBe('Equipe de Infraestrutura');

    $this->assertDatabaseHas('equipe', [
        'nome' => 'Equipe de Infraestrutura',
        'matricula_gestor' => $gestor->matricula_funcionario,
    ]);
});

test('admin can delete a team from database', function () {
    actingAsAdmin();

    $team = Equipe::query()->firstOrFail();
    $teamId = $team->ID_equipe;

    $response = $this->deleteJson(route('api.teams.destroy', ['team' => $teamId]));

    $response->assertOk();

    $this->assertDatabaseMissing('equipe', [
        'ID_equipe' => $teamId,
    ]);
});
