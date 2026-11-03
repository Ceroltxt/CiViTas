<?php

use App\Models\Identity\Funcionario;
use App\Models\Workspace\Workspace;
use App\Models\Team\Equipe;
use App\Models\Task\Tarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Project\Projeto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('gestor can export workspace projects as CSV', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    $workspace = Workspace::query()
        ->where('nome', 'Workspace Padrão')
        ->firstOrFail();

    Sanctum::actingAs($gestor);

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $workspace->id)
        ->get(route('api.projects.export', [
            'start_date' => now()->subDays(15)->format('Y-m-d'),
            'end_date' => now()->addDays(20)->format('Y-m-d'),
        ]));

    $response->assertOk();

    expect($response->headers->get('content-type'))
        ->toContain('text/csv');

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain("\xEF\xBB\xBF")
        ->toContain('ID_projeto;nome;descricao')
        ->toContain('Nova Praça Central')
        ->toContain('total_tarefas')
        ->toContain('progresso_periodo');
});

test('project CSV export requires a valid date range', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    $workspace = Workspace::query()
        ->where('nome', 'Workspace Padrão')
        ->firstOrFail();

    Sanctum::actingAs($gestor);

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $workspace->id)
        ->getJson(route('api.projects.export', [
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->subDays(10)->format('Y-m-d'),
        ]));

    $response->assertUnprocessable();
});

test('project export does not include data from another workspace', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    $workspace = Workspace::query()
        ->where('nome', 'Workspace Padrão')
        ->firstOrFail();

    $projeto = Projeto::query()
        ->where('nome', 'Nova Praça Central')
        ->firstOrFail();

    $status = StatusTarefa::query()
        ->where('nome_status', 'a-fazer')
        ->firstOrFail();

    Sanctum::actingAs($gestor);

    $startDate = now()->subDays(15)->format('Y-m-d');
    $endDate = now()->addDays(20)->format('Y-m-d');

    $responseBefore = $this
        ->withHeader('X-Workspace-Id', (string) $workspace->id)
        ->get(route('api.projects.export', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $responseBefore->assertOk();

    $csvBefore = $responseBefore->streamedContent();

    expect($csvBefore)->toContain('Nova Praça Central');

    $outroWorkspace = Workspace::query()->create([
        'nome' => 'Outro Workspace',
    ]);

    $outraEquipe = Equipe::query()->create([
        'nome' => 'Equipe de Outro Workspace',
        'pontos_totais' => 0,
        'matricula_gestor' => $gestor->matricula_funcionario,
        'workspace_id' => $outroWorkspace->id,
    ]);

    // O projeto também passa a estar associado à equipe do outro workspace.
    $projeto->equipes()->syncWithoutDetaching([
        $outraEquipe->ID_equipe,
    ]);

    Tarefa::query()->create([
        'nome' => 'Tarefa exclusiva do outro workspace',
        'descricao' => 'Não deve interferir na exportação atual.',
        'prioridade' => 'media',
        'data_inicio' => now()->subDays(2),
        'data_prazo' => now()->addDays(5),
        'pessoal' => false,
        'matricula_gestor' => $gestor->matricula_funcionario,
        'ID_projeto' => $projeto->ID_projeto,
        'ID_equipe' => $outraEquipe->ID_equipe,
        'ID_status_tarefa' => $status->ID_status_tarefa,
    ]);

    $responseAfter = $this
        ->withHeader('X-Workspace-Id', (string) $workspace->id)
        ->get(route('api.projects.export', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

    $responseAfter->assertOk();

    $csvAfter = $responseAfter->streamedContent();

    expect($csvAfter)
        ->toBe($csvBefore)
        ->not->toContain('Equipe de Outro Workspace');
});
test('project export includes tasks that were in progress during the selected period', function () {
    $gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    $workspace = Workspace::query()
        ->where('nome', 'Workspace Padrão')
        ->firstOrFail();

    $projeto = Projeto::query()
        ->where('nome', 'Nova Praça Central')
        ->firstOrFail();

    $equipe = $projeto->equipes()
        ->where('workspace_id', $workspace->id)
        ->firstOrFail();

    $status = StatusTarefa::query()
        ->where('nome_status', 'concluido')
        ->firstOrFail();

    Tarefa::query()->create([
        'nome' => 'Tarefa do projeto iniciada antes e concluída durante o período',
        'prioridade' => 'media',
        'data_inicio' => '2026-10-01',
        'data_prazo' => '2026-10-05',
        'data_conclusao' => '2026-10-12',
        'pessoal' => false,
        'matricula_gestor' => $gestor->matricula_funcionario,
        'ID_projeto' => $projeto->ID_projeto,
        'ID_equipe' => $equipe->ID_equipe,
        'ID_status_tarefa' => $status->ID_status_tarefa,
    ]);

    Sanctum::actingAs($gestor);

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $workspace->id)
        ->get(route('api.projects.export', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-15',
        ]));

    $response->assertOk();

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('Nova Praça Central')
        ->toContain('total_tarefas');
});