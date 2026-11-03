<?php

use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use App\Models\Workspace\Workspace;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->gestor = Funcionario::query()
        ->where('email', 'gestor@civitas.test')
        ->firstOrFail();

    $this->workspace = Workspace::query()
        ->where('nome', 'Workspace Padrão')
        ->firstOrFail();

    $this->projeto = Projeto::query()
        ->whereHas('equipes', function ($query) {
            $query->where(
                'equipe.workspace_id',
                $this->workspace->id
            );
        })
        ->firstOrFail();

    $this->equipe = $this->projeto->equipes()
        ->where('equipe.workspace_id', $this->workspace->id)
        ->firstOrFail();

    $matricula = DB::table('workspace_funcionario')
        ->where('workspace_id', $this->workspace->id)
        ->value('matricula_funcionario');

    $this->colaborador = Funcionario::findOrFail($matricula);
});

test('gestor can preview a valid task CSV without creating tasks', function () {
    Sanctum::actingAs($this->gestor);

    $csv = implode("\n", [
        'nome;descricao;prioridade;data_prazo;ID_projeto;ID_equipe;matriculas_colaboradores;subtarefas',
        implode(';', [
            'Tarefa de prévia CSV',
            'Descrição de teste',
            'alta',
            '2026-12-15',
            $this->projeto->ID_projeto,
            $this->equipe->ID_equipe,
            $this->colaborador->matricula_funcionario,
            'Coletar dados|Revisar resultados',
        ]),
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'tarefas.csv',
        $csv
    );

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $this->workspace->id)
        ->post(route('api.tasks.import.preview'), [
            'file' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('total_rows', 1)
        ->assertJsonPath('valid_rows', 1)
        ->assertJsonPath('invalid_rows', 0)
        ->assertJsonPath('rows.0.valid', true);

    expect(
        Tarefa::query()
            ->where('nome', 'Tarefa de prévia CSV')
            ->exists()
    )->toBeFalse();
});

test('gestor can confirm task CSV import with relationships', function () {
    Sanctum::actingAs($this->gestor);

    $csv = implode("\n", [
        'nome;descricao;prioridade;data_prazo;ID_projeto;ID_equipe;matriculas_colaboradores;subtarefas',
        implode(';', [
            'Tarefa importada CSV',
            'Descrição da tarefa importada',
            'alta',
            '2026-12-15',
            $this->projeto->ID_projeto,
            $this->equipe->ID_equipe,
            $this->colaborador->matricula_funcionario,
            'Coletar dados|Revisar resultados',
        ]),
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'tarefas.csv',
        $csv
    );

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $this->workspace->id)
        ->post(route('api.tasks.import.confirm'), [
            'file' => $file,
        ]);

    $response->assertCreated()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('imported_rows', 1);

    $taskId = $response->json('task_ids.0');

    expect($taskId)->not->toBeNull();

    $this->assertDatabaseHas('tarefa', [
        'ID_tarefa' => $taskId,
        'nome' => 'Tarefa importada CSV',
        'ID_projeto' => $this->projeto->ID_projeto,
        'ID_equipe' => $this->equipe->ID_equipe,
    ]);

    $this->assertDatabaseHas('tarefa_funcionario', [
        'ID_tarefa' => $taskId,
        'matricula_colaborador' => $this->colaborador->matricula_funcionario,
    ]);

    expect(
        DB::table('subtarefa')
            ->where('ID_tarefa', $taskId)
            ->count()
    )->toBe(2);

    $this->assertDatabaseHas('historico_tarefa', [
        'ID_tarefa' => $taskId,
        'acao' => 'Tarefa importada por CSV',
    ]);
});

test('invalid CSV confirmation does not import any task', function () {
    Sanctum::actingAs($this->gestor);

    $csv = implode("\n", [
        'nome;descricao;prioridade;data_prazo;ID_projeto;ID_equipe;matriculas_colaboradores;subtarefas',

        implode(';', [
            'Tarefa válida',
            'Esta linha não deve ser importada',
            'alta',
            '2026-12-15',
            $this->projeto->ID_projeto,
            $this->equipe->ID_equipe,
            $this->colaborador->matricula_funcionario,
            '',
        ]),

        implode(';', [
            'Tarefa inválida',
            'Prioridade incorreta',
            'urgente',
            '2026-12-15',
            $this->projeto->ID_projeto,
            $this->equipe->ID_equipe,
            $this->colaborador->matricula_funcionario,
            '',
        ]),
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'tarefas.csv',
        $csv
    );

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $this->workspace->id)
        ->post(route('api.tasks.import.confirm'), [
            'file' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('total_rows', 2)
        ->assertJsonPath('valid_rows', 1)
        ->assertJsonPath('invalid_rows', 1);

    expect(
        Tarefa::query()
            ->whereIn('nome', [
                'Tarefa válida',
                'Tarefa inválida',
            ])
            ->count()
    )->toBe(0);
});

test('import preview rejects a team from another workspace', function () {
    Sanctum::actingAs($this->gestor);

    $outroWorkspace = Workspace::query()->create([
        'nome' => 'Workspace de Teste',
    ]);

    $outraEquipe = Equipe::query()->create([
        'nome' => 'Equipe de Outro Workspace',
        'pontos_totais' => 0,
        'matricula_gestor' => $this->gestor->matricula_funcionario,
        'workspace_id' => $outroWorkspace->id,
    ]);

    $csv = implode("\n", [
        'nome;descricao;prioridade;data_prazo;ID_equipe',
        implode(';', [
            'Tarefa fora do workspace',
            'Tentativa de acesso a outra equipe',
            'alta',
            '2026-12-15',
            $outraEquipe->ID_equipe,
        ]),
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'tarefas.csv',
        $csv
    );

    $response = $this
        ->withHeader('X-Workspace-Id', (string) $this->workspace->id)
        ->post(route('api.tasks.import.preview'), [
            'file' => $file,
        ]);

    $response->assertUnprocessable()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('rows.0.valid', false)
        ->assertJsonPath(
            'rows.0.errors.0',
            'A equipe não existe ou não pertence ao workspace ativo.'
        );

    expect(
        Tarefa::query()
            ->where('nome', 'Tarefa fora do workspace')
            ->exists()
    )->toBeFalse();
});