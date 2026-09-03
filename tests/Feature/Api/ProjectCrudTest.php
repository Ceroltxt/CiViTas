<?php

use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsAdminProject(): Funcionario
{
    $admin = Funcionario::query()->where('email', 'admin@civitas.test')->firstOrFail();
    Sanctum::actingAs($admin);

    return $admin;
}

test('admin can create a new project in database', function () {
    actingAsAdminProject();

    $response = $this->postJson(route('api.projects.store'), [
        'nome' => 'Projeto Novo Centro Urbano',
        'descricao' => 'Revitalização do centro da cidade com novos espaços públicos',
        'prioridade' => 'alta',
        'data_inicio' => now()->format('Y-m-d'),
        'data_previsao_fim' => now()->addMonths(6)->format('Y-m-d'),
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('projeto', [
        'nome' => 'Projeto Novo Centro Urbano',
        'prioridade' => 'alta',
    ]);
});

test('admin can update project details', function () {
    actingAsAdminProject();

    $project = Projeto::query()->firstOrFail();

    $response = $this->putJson(route('api.projects.update', ['project' => $project->ID_projeto]), [
        'nome' => 'Projeto Nome Atualizado',
        'prioridade' => 'baixa',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('projeto', [
        'ID_projeto' => $project->ID_projeto,
        'nome' => 'Projeto Nome Atualizado',
        'prioridade' => 'baixa',
    ]);
});

test('admin can delete a project from database', function () {
    actingAsAdminProject();

    $project = Projeto::query()->firstOrFail();
    $projectId = $project->ID_projeto;

    $response = $this->deleteJson(route('api.projects.destroy', ['project' => $projectId]));

    $response->assertOk();

    $this->assertDatabaseMissing('projeto', [
        'ID_projeto' => $projectId,
    ]);
});
