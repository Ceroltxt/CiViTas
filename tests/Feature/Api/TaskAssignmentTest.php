<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsGestorUser(): Funcionario
{
    $funcionario = Funcionario::query()->where('email', 'gestor@civitas.test')->firstOrFail();
    Sanctum::actingAs($funcionario);

    return $funcionario;
}

test('gestor can create and assign task to collaborator', function () {
    $gestor = actingAsGestorUser();

    $colaborador = Funcionario::query()->where('email', 'ana@civitas.test')->firstOrFail();

    $response = $this->postJson(route('api.tasks.store'), [
        'nome' => 'Nova Tarefa de Inspecao Tecnica',
        'descricao' => 'Verificar instalacoes eletricas e seguranca.',
        'prioridade' => 'alta',
        'data_prazo' => now()->addDays(5)->format('Y-m-d'),
        'matricula_colaborador' => [$colaborador->matricula_funcionario],
        'subtarefas' => ['Testar cabos', 'Verificar gerador'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('title', 'Nova Tarefa de Inspecao Tecnica')
        ->assertJsonPath('priority', 'alta')
        ->assertJsonPath('status', 'a-fazer');

    $tarefa = Tarefa::query()->where('nome', 'Nova Tarefa de Inspecao Tecnica')->firstOrFail();
    expect($tarefa->colaboradores->pluck('matricula_funcionario'))->toContain($colaborador->matricula_funcionario);

    // Verificacao do historico gravado
    expect(HistoricoTarefa::query()->where('ID_tarefa', $tarefa->ID_tarefa)->exists())->toBeTrue();
});

test('task creation requires mandatory fields', function () {
    actingAsGestorUser();

    $response = $this->postJson(route('api.tasks.store'), [
        'nome' => '',
        'prioridade' => 'invalida',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['nome', 'prioridade', 'data_prazo']);
});
