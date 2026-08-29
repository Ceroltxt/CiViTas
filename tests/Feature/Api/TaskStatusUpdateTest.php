<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsColaboradorForTask(): Funcionario
{
    $funcionario = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();
    Sanctum::actingAs($funcionario);

    return $funcionario;
}

test('assigned colaborador can update task status to em-andamento', function () {
    $funcionario = actingAsColaboradorForTask();

    $tarefa = Tarefa::query()->where('nome', 'Fiscalizar obra da Nova Praça Central')->firstOrFail();

    $response = $this->patchJson(route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]), [
        'status' => 'em-andamento',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'em-andamento');

    $statusEmAndamento = StatusTarefa::query()->where('nome_status', 'em-andamento')->firstOrFail();
    expect($tarefa->fresh()->ID_status_tarefa)->toBe($statusEmAndamento->ID_status_tarefa);

    // Verificação da gravação no histórico de tarefas
    expect(HistoricoTarefa::query()->where('ID_tarefa', $tarefa->ID_tarefa)->exists())->toBeTrue();
});

test('updating task status to concluido sets completion date', function () {
    actingAsColaboradorForTask();

    $tarefa = Tarefa::query()->where('nome', 'Fiscalizar obra da Nova Praça Central')->firstOrFail();

    $response = $this->patchJson(route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]), [
        'status' => 'concluido',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'concluido');

    expect($tarefa->fresh()->data_conclusao)->not()->toBeNull();
});

test('updating task to invalid status returns validation error', function () {
    actingAsColaboradorForTask();

    $tarefa = Tarefa::query()->where('nome', 'Fiscalizar obra da Nova Praça Central')->firstOrFail();

    $response = $this->patchJson(route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]), [
        'status' => 'status-inexistente-invalido',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('unauthenticated request to update task status is rejected', function () {
    $tarefa = Tarefa::query()->firstOrFail();

    $this->patchJson(route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]), [
        'status' => 'concluido',
    ])->assertUnauthorized();
});
