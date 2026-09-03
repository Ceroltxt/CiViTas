<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsGestorCrud(): Funcionario
{
    $gestor = Funcionario::query()->where('email', 'gestor@civitas.test')->firstOrFail();
    Sanctum::actingAs($gestor);

    return $gestor;
}

test('gestor can update task details in database', function () {
    actingAsGestorCrud();

    $task = Tarefa::query()->firstOrFail();

    $response = $this->putJson(route('api.tasks.update', ['task' => $task->ID_tarefa]), [
        'nome' => 'Título Atualizado pelo Teste',
        'descricao' => 'Nova descrição de teste',
        'prioridade' => 'alta',
        'data_prazo' => now()->addDays(5)->format('Y-m-d'),
    ]);

    $response->assertOk();

    $title = $response->json('title') ?? $response->json('data.title');
    expect($title)->toBe('Título Atualizado pelo Teste');

    $this->assertDatabaseHas('tarefa', [
        'ID_tarefa' => $task->ID_tarefa,
        'nome' => 'Título Atualizado pelo Teste',
        'prioridade' => 'alta',
    ]);
});

test('user can delete a task from database', function () {
    actingAsGestorCrud();

    $task = Tarefa::query()->firstOrFail();
    $taskId = $task->ID_tarefa;

    $response = $this->deleteJson(route('api.tasks.destroy', ['task' => $taskId]));

    $response->assertOk();

    $this->assertDatabaseMissing('tarefa', [
        'ID_tarefa' => $taskId,
    ]);
});
