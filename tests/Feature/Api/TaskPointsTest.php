<?php

use App\Application\Gamification\TaskPointsService;
use App\Models\Gamification\PontosUsuario;
use App\Models\Identity\Funcionario;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $this->colaborador = Funcionario::query()
        ->where('email', 'ana@civitas.test')
        ->firstOrFail();

    $this->pontosIniciais = (int) $this->colaborador->pontos_totais;
});

test('atribui dois pontos quando a tarefa e concluida antes do prazo', function () {
    $this->tarefa->data_prazo = '2026-10-20';
    $this->tarefa->data_conclusao = '2026-10-19';
    $this->tarefa->save();

    app(TaskPointsService::class)->awardForCompletion($this->tarefa->fresh());

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais + 2);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $this->colaborador->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => 2,
        'chave_idempotencia' => "conclusao:{$this->tarefa->ID_tarefa}:{$this->colaborador->matricula_funcionario}",
    ]);
});

test('atribui um ponto quando a tarefa e concluida no prazo', function () {
    $this->tarefa->data_prazo = '2026-10-20';
    $this->tarefa->data_conclusao = '2026-10-20';
    $this->tarefa->save();

    app(TaskPointsService::class)->awardForCompletion($this->tarefa->fresh());

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais + 1);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $this->colaborador->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => 1,
    ]);
});

test('nao atribui pontos quando a tarefa e concluida atrasada', function () {
    $this->tarefa->data_prazo = '2026-10-19';
    $this->tarefa->data_conclusao = '2026-10-20';
    $this->tarefa->save();

    app(TaskPointsService::class)->awardForCompletion($this->tarefa->fresh());

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $this->colaborador->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => 0,
    ]);
});

test('nao duplica a pontuacao ao processar a mesma conclusao duas vezes', function () {
    $this->tarefa->data_prazo = '2026-10-20';
    $this->tarefa->data_conclusao = '2026-10-19';
    $this->tarefa->save();

    $service = app(TaskPointsService::class);

    $service->awardForCompletion($this->tarefa->fresh());
    $service->awardForCompletion($this->tarefa->fresh());

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais + 2);

    expect(
        PontosUsuario::query()
            ->where('chave_idempotencia', "conclusao:{$this->tarefa->ID_tarefa}:{$this->colaborador->matricula_funcionario}")
            ->count()
    )->toBe(1);
});



test('penaliza em dois pontos cada colaborador na primeira reabertura', function () {
    $this->tarefa->pessoal = false;
    $this->tarefa->data_conclusao = null;
    $this->tarefa->foi_reaberta = true;
    $this->tarefa->quantidade_reaberturas = 1;
    $this->tarefa->save();

    app(TaskPointsService::class)->penalizeReopening(
        $this->tarefa->fresh(),
        1
    );

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais - 2);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $this->colaborador->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => -2,
        'chave_idempotencia' => "reabertura:{$this->tarefa->ID_tarefa}:1:{$this->colaborador->matricula_funcionario}",
    ]);
});

test('nao duplica a penalidade ao processar a mesma reabertura duas vezes', function () {
    $this->tarefa->pessoal = false;
    $this->tarefa->data_conclusao = null;
    $this->tarefa->foi_reaberta = true;
    $this->tarefa->quantidade_reaberturas = 1;
    $this->tarefa->save();

    $service = app(TaskPointsService::class);

    $service->penalizeReopening($this->tarefa->fresh(), 1);
    $service->penalizeReopening($this->tarefa->fresh(), 1);

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais - 2);

    expect(
        PontosUsuario::query()
            ->where(
                'chave_idempotencia',
                "reabertura:{$this->tarefa->ID_tarefa}:1:{$this->colaborador->matricula_funcionario}"
            )
            ->count()
    )->toBe(1);
});

test('aplica nova penalidade em uma segunda reabertura valida', function () {
    $this->tarefa->pessoal = false;
    $this->tarefa->data_conclusao = null;
    $this->tarefa->foi_reaberta = true;
    $this->tarefa->quantidade_reaberturas = 1;
    $this->tarefa->save();

    $service = app(TaskPointsService::class);

    $service->penalizeReopening($this->tarefa->fresh(), 1);

    $this->tarefa->quantidade_reaberturas = 2;
    $this->tarefa->save();

    $service->penalizeReopening($this->tarefa->fresh(), 2);

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais - 4);

    expect(
        PontosUsuario::query()
            ->where('ID_tarefa', $this->tarefa->ID_tarefa)
            ->where('pontos', -2)
            ->where('acao', 'Penalidade por reabertura de tarefa')
            ->count()
    )->toBe(2);
});

test('atribui pontos a todos os colaboradores da tarefa', function () {
    $juliana = Funcionario::query()
        ->where('email', 'juliana@civitas.test')
        ->firstOrFail();

    $pontosIniciaisJuliana = (int) $juliana->pontos_totais;

    $this->tarefa->colaboradores()->syncWithoutDetaching([
        $juliana->matricula_funcionario,
    ]);

    $this->tarefa->data_prazo = '2026-10-20';
    $this->tarefa->data_conclusao = '2026-10-19';
    $this->tarefa->save();

    app(TaskPointsService::class)->awardForCompletion(
        $this->tarefa->fresh()
    );

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais + 2);

    expect((int) $juliana->fresh()->pontos_totais)
        ->toBe($pontosIniciaisJuliana + 2);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $juliana->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => 2,
    ]);
});

test('nao altera a pontuacao de tarefa pessoal', function () {
    $tarefaPessoal = Tarefa::query()
        ->where('nome', 'Agendar férias')
        ->firstOrFail();

    $tarefaPessoal->colaboradores()->sync([
        $this->colaborador->matricula_funcionario,
    ]);

    $tarefaPessoal->data_conclusao = '2026-10-19';
    $tarefaPessoal->save();

    app(TaskPointsService::class)->awardForCompletion(
        $tarefaPessoal->fresh()
    );

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais);

    expect(
        PontosUsuario::query()
            ->where('ID_tarefa', $tarefaPessoal->ID_tarefa)
            ->count()
    )->toBe(0);
});

test('registra zero pontos quando a tarefa nao tem prazo', function () {
    $this->tarefa->data_prazo = null;
    $this->tarefa->data_conclusao = '2026-10-19';
    $this->tarefa->save();

    app(TaskPointsService::class)->awardForCompletion(
        $this->tarefa->fresh()
    );

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $this->colaborador->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => 0,
    ]);
});

test('penaliza todos os colaboradores na reabertura', function () {
    $juliana = Funcionario::query()
        ->where('email', 'juliana@civitas.test')
        ->firstOrFail();

    $pontosIniciaisJuliana = (int) $juliana->pontos_totais;

    $this->tarefa->colaboradores()->syncWithoutDetaching([
        $juliana->matricula_funcionario,
    ]);

    $this->tarefa->pessoal = false;
    $this->tarefa->data_conclusao = null;
    $this->tarefa->foi_reaberta = true;
    $this->tarefa->quantidade_reaberturas = 1;
    $this->tarefa->save();

    app(TaskPointsService::class)->penalizeReopening(
        $this->tarefa->fresh(),
        1
    );

    expect((int) $this->colaborador->fresh()->pontos_totais)
        ->toBe($this->pontosIniciais - 2);

    expect((int) $juliana->fresh()->pontos_totais)
        ->toBe($pontosIniciaisJuliana - 2);

    $this->assertDatabaseHas('pontos_usuario', [
        'matricula_funcionario' => $juliana->matricula_funcionario,
        'ID_tarefa' => $this->tarefa->ID_tarefa,
        'pontos' => -2,
        'chave_idempotencia' => "reabertura:{$this->tarefa->ID_tarefa}:1:{$juliana->matricula_funcionario}",
    ]);
});