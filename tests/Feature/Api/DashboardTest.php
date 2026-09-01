<?php

use App\Models\Identity\Funcionario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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
