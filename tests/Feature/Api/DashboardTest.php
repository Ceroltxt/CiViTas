<?php

use App\Models\Identity\Funcionario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use App\Domain\Authorization\AppProfile;
use App\Models\Gamification\PontosUsuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function actingAsGestorDashboard(): Funcionario
{
    $gestor = Funcionario::query()->where('email', 'gestor@civitas.test')->firstOrFail();
    Sanctum::actingAs($gestor);

    return $gestor;
}

test('gestor can fetch real dashboard metrics from database', function () {
    actingAsGestorDashboard();

    $response = $this->getJson(route('api.dashboard.metrics'));

    $response->assertOk()
        ->assertJsonIsArray();

    $metrics = $response->json();
    expect($metrics)->not()->toBeEmpty();

    $ids = array_column($metrics, 'id');
    expect($ids)->toContain('atribuidas', 'andamento', 'atraso', 'produtividade');
});

test('authenticated user can fetch dashboard projects', function () {
    actingAsGestorDashboard();

    $response = $this->getJson(route('api.dashboard.projects'));

    $response->assertOk();
    $data = $response->json('data') ?? $response->json();
    expect($data)->not()->toBeEmpty();
    expect($data[0])->toHaveKeys(['id', 'name', 'progress', 'color']);
});

test('authenticated user can fetch ranking entries', function () {
    actingAsGestorDashboard();

    $response = $this->getJson(route('api.dashboard.ranking'));

    $response->assertOk()
        ->assertJsonIsArray();
});

test('ranking mensal inclui colaboradores com pontuação positiva e negativa', function () {
    actingAsGestorDashboard();

    $colaboradores = Funcionario::query()
        ->with('cargo')
        ->get()
        ->filter(
            fn (Funcionario $funcionario) =>
                $funcionario->profile() === AppProfile::Colaborador
        )
        ->values();

    expect($colaboradores->count())->toBeGreaterThanOrEqual(2);

    $positivo = $colaboradores[0];
    $negativo = $colaboradores[1];

    $inicioMes = now()->startOfMonth();

    PontosUsuario::query()->create([
        'pontos' => 5,
        'acao' => 'Teste de ranking',
        'data' => $inicioMes->copy()->addDay(),
        'matricula_funcionario' => $positivo->matricula_funcionario,
        'chave_idempotencia' => 'teste-ranking-positivo',
    ]);

    PontosUsuario::query()->create([
        'pontos' => -2,
        'acao' => 'Teste de penalidade',
        'data' => $inicioMes->copy()->addDays(2),
        'matricula_funcionario' => $negativo->matricula_funcionario,
        'chave_idempotencia' => 'teste-ranking-negativo',
    ]);

    Cache::forget('dashboard_ranking_' . now()->format('Y-m'));

    $response = $this->getJson(route('api.dashboard.ranking', [
        'month' => now()->format('Y-m'),
    ]));

    $response->assertOk()->assertJsonIsArray();

    $ranking = collect($response->json());

    $entradaPositiva = $ranking->firstWhere(
        'user.id',
        (string) $positivo->matricula_funcionario
    );

    $entradaNegativa = $ranking->firstWhere(
        'user.id',
        (string) $negativo->matricula_funcionario
    );

    expect($entradaPositiva)->not->toBeNull();
    expect($entradaPositiva['stars'])->toBe(5);

    expect($entradaNegativa)->not->toBeNull();
    expect($entradaNegativa['stars'])->toBe(-2);
});

test('ranking mensal rejeita meses fora da janela permitida', function () {
    actingAsGestorDashboard();

    $mesInvalido = now()->startOfMonth()->subMonths(3)->format('Y-m');

    $this->getJson(route('api.dashboard.ranking', [
        'month' => $mesInvalido,
    ]))->assertUnprocessable();
});

test('ranking mensal mantém as pontuações de cada mês independentes', function () {
    actingAsGestorDashboard();

    $colaborador = Funcionario::query()
        ->with('cargo')
        ->get()
        ->first(
            fn (Funcionario $funcionario) =>
                $funcionario->profile() === AppProfile::Colaborador
        );

    expect($colaborador)->not->toBeNull();

    $mesAtual = now()->startOfMonth();
    $mesAnterior = $mesAtual->copy()->subMonth();

    PontosUsuario::query()->create([
        'pontos' => 7,
        'acao' => 'Teste ranking mês atual',
        'data' => $mesAtual->copy()->addDay(),
        'matricula_funcionario' => $colaborador->matricula_funcionario,
        'chave_idempotencia' => 'teste-ranking-mes-atual',
    ]);

    PontosUsuario::query()->create([
        'pontos' => 3,
        'acao' => 'Teste ranking mês anterior',
        'data' => $mesAnterior->copy()->addDay(),
        'matricula_funcionario' => $colaborador->matricula_funcionario,
        'chave_idempotencia' => 'teste-ranking-mes-anterior',
    ]);

    Cache::forget('dashboard_ranking_' . $mesAtual->format('Y-m'));
    Cache::forget('dashboard_ranking_' . $mesAnterior->format('Y-m'));

    $responseAtual = $this->getJson(route('api.dashboard.ranking', [
        'month' => $mesAtual->format('Y-m'),
    ]));

    $responseAtual->assertOk();
    $rankingAtual = collect($responseAtual->json());

    $responseAnterior = $this->getJson(route('api.dashboard.ranking', [
        'month' => $mesAnterior->format('Y-m'),
    ]));

    $responseAnterior->assertOk();
    $rankingAnterior = collect($responseAnterior->json());

    $id = (string) $colaborador->matricula_funcionario;

    $entradaAtual = $rankingAtual->firstWhere('user.id', $id);
    $entradaAnterior = $rankingAnterior->firstWhere('user.id', $id);

    expect($entradaAtual)->not->toBeNull();
    expect($entradaAtual['stars'])->toBe(7);

    expect($entradaAnterior)->not->toBeNull();
    expect($entradaAnterior['stars'])->toBe(3);
});

test('ranking mensal compartilha posições em caso de empate', function () {
    actingAsGestorDashboard();

    $colaboradores = Funcionario::query()
        ->with('cargo')
        ->get()
        ->filter(
            fn (Funcionario $funcionario) =>
                $funcionario->profile() === AppProfile::Colaborador
        )
        ->values();

    if ($colaboradores->count() < 3) {
        $departamento = $colaboradores[0]->ID_departamento;
        $cargo = $colaboradores[0]->ID_cargo;

        $terceiro = Funcionario::query()->create([
            'nome' => 'Teste',
            'sobrenome' => 'Ranking',
            'data_nascimento' => '1995-01-01',
            'email' => 'ranking.terceiro@civitas.test',
            'CPF' => '99988877766',
            'pontos_totais' => 0,
            'senha' => 'senha-temporaria',
            'ID_departamento' => $departamento,
            'ID_cargo' => $cargo,
        ]);

        $colaboradores->push($terceiro->load('cargo'));
    }

    $mes = now()->startOfMonth();
    $chaves = [
        'teste-ranking-empate-a',
        'teste-ranking-empate-b',
        'teste-ranking-empate-c',
    ];

    foreach ([0 => 10, 1 => 10, 2 => 5] as $indice => $pontos) {
        PontosUsuario::query()->create([
            'pontos' => $pontos,
            'acao' => 'Teste de empate',
            'data' => $mes->copy()->addDay(),
            'matricula_funcionario' => $colaboradores[$indice]->matricula_funcionario,
            'chave_idempotencia' => $chaves[$indice],
        ]);
    }

    Cache::forget('dashboard_ranking_' . $mes->format('Y-m'));

    $response = $this->getJson(route('api.dashboard.ranking', [
        'month' => $mes->format('Y-m'),
    ]));

    $response->assertOk();

    $ranking = collect($response->json());

    $entradaA = $ranking->firstWhere(
        'user.id',
        (string) $colaboradores[0]->matricula_funcionario
    );

    $entradaB = $ranking->firstWhere(
        'user.id',
        (string) $colaboradores[1]->matricula_funcionario
    );

    $entradaC = $ranking->firstWhere(
        'user.id',
        (string) $colaboradores[2]->matricula_funcionario
    );

    expect($entradaA['stars'])->toBe(10);
    expect($entradaB['stars'])->toBe(10);
    expect($entradaA['position'])->toBe(1);
    expect($entradaB['position'])->toBe(1);

    expect($entradaC['stars'])->toBe(5);
    expect($entradaC['position'])->toBe(3);
});

test('ranking mensal inclui colaboradores sem pontuacao', function () {
    actingAsGestorDashboard();

    $colaborador = Funcionario::query()
        ->where('email', 'ana@civitas.test')
        ->firstOrFail();

    $mes = now()->startOfMonth();

    Cache::forget('dashboard_ranking_' . $mes->format('Y-m'));

    $response = $this->getJson(route('api.dashboard.ranking', [
        'month' => $mes->format('Y-m'),
    ]));

    $response->assertOk()->assertJsonIsArray();

    $ranking = collect($response->json());

    $entrada = $ranking->firstWhere(
        'user.id',
        (string) $colaborador->matricula_funcionario
    );

    expect($entrada)->not->toBeNull();
    expect($entrada['stars'])->toBe(0);
});