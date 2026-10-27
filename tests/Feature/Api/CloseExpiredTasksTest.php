<?php

use App\Models\Identity\Funcionario;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\StatusTarefa;
use App\Models\Task\Tarefa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\Gamification\PontosUsuario;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function executarFechamentoAutomatico(): int
{
    return Artisan::call('tasks:close-expired');
}

test('fecha automaticamente tarefa cujo horario foi atingido', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $tarefa->data_fechamento_automatico = now()->subMinute();
    $tarefa->data_conclusao = null;
    $tarefa->save();

    $resultado = executarFechamentoAutomatico();

    $fechada = StatusTarefa::query()
        ->where('nome_status', 'fechada')
        ->firstOrFail();

    expect($resultado)->toBe(0);
    expect((int) $tarefa->fresh()->ID_status_tarefa)
        ->toBe((int) $fechada->ID_status_tarefa);

    expect(
        HistoricoTarefa::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->where('acao', 'Fechamento automático da tarefa')
            ->count()
    )->toBe(1);

    $historico = HistoricoTarefa::query()
    ->where('ID_tarefa', $tarefa->ID_tarefa)
    ->where('acao', 'Fechamento automático da tarefa')
    ->firstOrFail();

    expect($historico->detalhes)
        ->toContain('Tarefa fechada automaticamente.');

    expect($historico->detalhes)
        ->toContain('Horário configurado:');

    expect((int) $historico->matricula_funcionario)
        ->toBe((int) $tarefa->matricula_gestor);
});

test('nao fecha tarefa cujo horario ainda nao chegou', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $statusOriginal = (int) $tarefa->ID_status_tarefa;

    $tarefa->data_fechamento_automatico = now()->addHour();
    $tarefa->data_conclusao = null;
    $tarefa->save();

    expect(executarFechamentoAutomatico())->toBe(0);

    expect((int) $tarefa->fresh()->ID_status_tarefa)
        ->toBe($statusOriginal);

    expect(
        HistoricoTarefa::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->where('acao', 'Fechamento automático da tarefa')
            ->count()
    )->toBe(0);
});

test('nao fecha automaticamente tarefa ja concluida', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $statusOriginal = (int) $tarefa->ID_status_tarefa;

    $tarefa->data_fechamento_automatico = now()->subMinute();
    $tarefa->data_conclusao = now()->toDateString();
    $tarefa->save();

    expect(executarFechamentoAutomatico())->toBe(0);

    expect((int) $tarefa->fresh()->ID_status_tarefa)
        ->toBe($statusOriginal);

    expect(
        HistoricoTarefa::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->where('acao', 'Fechamento automático da tarefa')
            ->count()
    )->toBe(0);
});

test('nao registra fechamento automatico duplicado', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $tarefa->data_fechamento_automatico = now()->subMinute();
    $tarefa->data_conclusao = null;
    $tarefa->save();

    expect(executarFechamentoAutomatico())->toBe(0);
    expect(executarFechamentoAutomatico())->toBe(0);

    expect(
        HistoricoTarefa::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->where('acao', 'Fechamento automático da tarefa')
            ->count()
    )->toBe(1);
});


test('fechamento automatico nao altera pontuacao nem cria registros', function () {
    $tarefa = Tarefa::query()
        ->where('nome', 'Fiscalizar obra da Nova Praça Central')
        ->firstOrFail();

    $colaborador = Funcionario::query()
        ->where('email', 'ana@civitas.test')
        ->firstOrFail();

    $pontosAntes = (int) $colaborador->pontos_totais;

    $registrosAntes = PontosUsuario::query()
        ->where('ID_tarefa', $tarefa->ID_tarefa)
        ->count();

    $tarefa->data_fechamento_automatico = now()->subMinute();
    $tarefa->data_conclusao = null;
    $tarefa->save();

    expect(executarFechamentoAutomatico())->toBe(0);

    expect((int) $colaborador->fresh()->pontos_totais)
        ->toBe($pontosAntes);

    expect(
        PontosUsuario::query()
            ->where('ID_tarefa', $tarefa->ID_tarefa)
            ->count()
    )->toBe($registrosAntes);
});