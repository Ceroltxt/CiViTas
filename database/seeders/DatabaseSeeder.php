<?php

namespace Database\Seeders;

use App\Models\Identity\Funcionario;
use App\Models\Organization\Cargo;
use App\Models\Organization\Departamento;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departamento = Departamento::query()->firstOrCreate(
            ['nome_departamento' => 'Engenharia'],
        );

        $cargo = Cargo::query()->firstOrCreate(
            ['nome_cargo' => 'Desenvolvedor'],
        );

        Funcionario::query()->updateOrCreate(
            ['email' => 'ana@civitas.test'],
            [
                'nome' => 'Ana',
                'sobrenome' => 'Silva',
                'data_nascimento' => '1995-04-12',
                'CPF' => '12345678901',
                'pontos_totais' => 10,
                'senha' => 'secret-password',
                'ID_departamento' => $departamento->getKey(),
                'ID_cargo' => $cargo->getKey(),
            ],
        );
    }
}
