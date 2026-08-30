<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\Subtarefa;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsColaboradorAna(): Funcionario
{
    $colaborador = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();
    Sanctum::actingAs($colaborador);

    return $colaborador;
}

test('user can fetch task details by id', function () {
    actingAsColaboradorAna();
    $tarefa = Tarefa::query()->firstOrFail();

    $response = $this->getJson(route('api.tasks.show', ['task' => $tarefa->ID_tarefa]));

    $response->assertOk()
        ->assertJsonPath('title', $tarefa->nome);
});

test('user can toggle a subtask completion state', function () {
    actingAsColaboradorAna();

    $tarefa = Tarefa::query()->first();
    $subtask = Subtarefa::query()->create([
        'nome' => 'Verificar valvulas de seguranca',
        'ID_tarefa' => $tarefa->ID_tarefa,
        'concluida' => false,
    ]);

    $response = $this->patchJson(route('api.tasks.subtasks.toggle', [
        'task' => $tarefa->ID_tarefa,
        'subtask' => $subtask->ID_subtarefa,
    ]));

    $response->assertOk();
    expect($subtask->fresh()->concluida)->toBeTrue();

    // Verificacao de log no historico
    expect(HistoricoTarefa::query()->where('ID_tarefa', $tarefa->ID_tarefa)->where('acao', 'Subtarefa concluída')->exists())->toBeTrue();
});

test('user can post a comment to task history', function () {
    $colaborador = actingAsColaboradorAna();
    $tarefa = Tarefa::query()->first();

    $response = $this->postJson(route('api.tasks.comments', ['task' => $tarefa->ID_tarefa]), [
        'comentario' => 'Conclui a analise do canteiro de obras.',
    ]);

    $response->assertOk();

    $historico = HistoricoTarefa::query()
        ->where('ID_tarefa', $tarefa->ID_tarefa)
        ->where('acao', 'Comentário adicionado')
        ->firstOrFail();

    expect($historico->detalhes)->toBe('Conclui a analise do canteiro de obras.');
    expect($historico->matricula_funcionario)->toBe($colaborador->matricula_funcionario);
});
