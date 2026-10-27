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

test('colaborador attempting concluido moves task to em-revisao', function () {
    actingAsColaboradorForTask();

    $tarefa = Tarefa::query()->where('nome', 'Fiscalizar obra da Nova Praça Central')->firstOrFail();

    $response = $this->patchJson(route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]), [
        'status' => 'concluido',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'em-revisao');

    $statusEmRevisao = StatusTarefa::query()->where('nome_status', 'em-revisao')->firstOrFail();
    expect($tarefa->fresh()->ID_status_tarefa)->toBe($statusEmRevisao->ID_status_tarefa);
});

test('gestor can conclude task directly setting completion date', function () {
    $gestor = Funcionario::query()->where('email', 'gestor@civitas.test')->firstOrFail();
    Sanctum::actingAs($gestor);

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


test('gestor reabre tarefa fechada e aplica penalidade aos colaboradores', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    Sanctum::actingAs($gestor);

    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $tarefa->pessoal = false;
    $tarefa->data_conclusao = null;
    $tarefa->foi_reaberta = false;
    $tarefa->quantidade_reaberturas = 0;
    $tarefa->save();

    $colaborador = Funcionario::query()
        ->where('email', 'ana@civitas.test')
        ->firstOrFail();

    $pontosIniciais = (int) $colaborador->pontos_totais;

    $fechar = $this->patchJson(
        route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]),
        ['status' => 'fechada']
    );

    $fechar->assertOk()
        ->assertJsonPath('status', 'fechada');

    $reabrir = $this->patchJson(
        route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]),
        ['status' => 'em-andamento']
    );

    $reabrir->assertOk()
        ->assertJsonPath('status', 'em-andamento');

    expect((int) $colaborador->fresh()->pontos_totais)
        ->toBe($pontosIniciais - 2);

    expect((int) $tarefa->fresh()->quantidade_reaberturas)
        ->toBe(1);
});

test('colaborador nao pode reabrir tarefa fechada', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $tarefa->data_conclusao = null;
    $tarefa->save();

    $fechada = StatusTarefa::query()
        ->where('nome_status', 'fechada')
        ->firstOrFail();

    $tarefa->ID_status_tarefa = $fechada->ID_status_tarefa;
    $tarefa->save();

    actingAsColaboradorForTask();

    $this->patchJson(
        route('api.tasks.update-status', ['task' => $tarefa->ID_tarefa]),
        ['status' => 'em-andamento']
    )->assertForbidden();

    expect((int) $tarefa->fresh()->quantidade_reaberturas)->toBe(0);
});


test('rollback mantem tarefa fechada se a penalidade falhar', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    Sanctum::actingAs($gestor);

    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $fechada = StatusTarefa::query()
        ->where('nome_status', 'fechada')
        ->firstOrFail();

    $tarefa->pessoal = false;
    $tarefa->data_conclusao = null;
    $tarefa->ID_status_tarefa = $fechada->ID_status_tarefa;
    $tarefa->foi_reaberta = false;
    $tarefa->quantidade_reaberturas = 0;
    $tarefa->save();

    $historicosAntes = HistoricoTarefa::query()
        ->where('ID_tarefa', $tarefa->ID_tarefa)
        ->count();

    $taskPoints = Mockery::mock(
        \App\Application\Gamification\TaskPointsService::class
    );

    $taskPoints->shouldReceive('penalizeReopening')
        ->once()
        ->andThrow(new \RuntimeException('Falha simulada na penalidade'));

    $this->app->instance(
        \App\Application\Gamification\TaskPointsService::class,
        $taskPoints
    );

    try {
        $this->patchJson(
            route('api.tasks.update-status', [
                'task' => $tarefa->ID_tarefa,
            ]),
            ['status' => 'em-andamento']
        );
    } catch (\RuntimeException $exception) {
        expect($exception->getMessage())
            ->toBe('Falha simulada na penalidade');
    }

    $tarefaDepois = $tarefa->fresh();

    expect($tarefaDepois->status->nome_status)->toBe('fechada');
    expect((int) $tarefaDepois->quantidade_reaberturas)->toBe(0);
    expect((bool) $tarefaDepois->foi_reaberta)->toBeFalse();

    expect(
        HistoricoTarefa::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->count()
    )->toBe($historicosAntes);
});
