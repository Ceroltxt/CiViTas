<?php

namespace App\Support\Import;

use App\Models\Identity\Funcionario;
use App\Models\Project\Projeto;
use App\Models\Task\HistoricoTarefa;
use App\Models\Task\Subtarefa;
use App\Models\Task\Tarefa;
use App\Models\Team\Equipe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskCsvImporter
{
    private const MAX_ROWS = 500;

    private const REQUIRED_HEADERS = [
        'nome',
        'prioridade',
        'data_prazo',
    ];

    private const OPTIONAL_HEADERS = [
        'descricao',
        'ID_projeto',
        'ID_equipe',
        'matriculas_colaboradores',
        'subtarefas',
    ];

    public function preview(UploadedFile $file): array
    {
        $result = $this->inspect($file);

        unset($result['_validated_rows']);

        return $result;
    }

    public function confirm(
        UploadedFile $file,
        Funcionario $funcionario
    ): array {
        $result = $this->inspect($file);

        if (! ($result['valid'] ?? false)) {
            unset($result['_validated_rows']);

            return $result;
        }

        $statusId = DB::table('status_tarefa')
            ->where('nome_status', 'a-fazer')
            ->value('ID_status_tarefa');

        if (! $statusId) {
            unset($result['_validated_rows']);

            $result['valid'] = false;
            $result['errors'][] = 'O status inicial "a-fazer" não foi encontrado no sistema.';

            return $result;
        }

        $workspaceId = (int) app('workspace_id');
        $rows = $result['_validated_rows'];

        $imported = DB::transaction(function () use (
            $rows,
            $funcionario,
            $statusId,
            $workspaceId
        ) {
            $createdTasks = [];

            foreach ($rows as $row) {
                $colaboradores = $row['matriculas_colaboradores'];

                if (empty($colaboradores)) {
                    $colaboradores = [
                        $funcionario->matricula_funcionario,
                    ];
                }

                $pontos = match ($row['prioridade']) {
                    'alta' => 200,
                    'media' => 100,
                    'baixa' => 50,
                };

                $tarefa = Tarefa::query()->create([
                    'nome' => $row['nome'],
                    'descricao' => $row['descricao'],
                    'prioridade' => $row['prioridade'],
                    'data_inicio' => now()->toDateString(),
                    'data_prazo' => $row['data_prazo'],
                    'pontos_base' => $pontos,
                    'pessoal' => DB::raw('false'),
                    'matricula_gestor' => $funcionario->matricula_funcionario,
                    'ID_projeto' => $row['ID_projeto'],
                    'ID_equipe' => $row['ID_equipe'],
                    'ID_status_tarefa' => $statusId,
                ]);

                foreach ($colaboradores as $matricula) {
                    DB::table('tarefa_funcionario')->insert([
                        'ID_tarefa' => $tarefa->ID_tarefa,
                        'matricula_colaborador' => $matricula,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($row['subtarefas'] as $nomeSubtarefa) {
                    Subtarefa::query()->create([
                        'nome' => $nomeSubtarefa,
                        'concluida' => DB::raw('false'),
                        'ID_tarefa' => $tarefa->ID_tarefa,
                        'matricula_colaborador' => $colaboradores[0],
                    ]);
                }

                HistoricoTarefa::query()->create([
                    'acao' => 'Tarefa importada por CSV',
                    'detalhes' => "Tarefa importada por CSV pelo gestor {$funcionario->nome}.",
                    'ID_tarefa' => $tarefa->ID_tarefa,
                    'matricula_funcionario' => $funcionario->matricula_funcionario,
                ]);

                $createdTasks[] = $tarefa->ID_tarefa;
            }

            return $createdTasks;
        });

        return [
            'valid' => true,
            'total_rows' => count($rows),
            'imported_rows' => count($imported),
            'message' => count($imported) . ' tarefa(s) importada(s) com sucesso.',
            'task_ids' => $imported,
        ];
    }

    private function inspect(UploadedFile $file): array
    {
        $handle = $this->openCsv($file);

        try {
            $headers = fgetcsv($handle, 0, ';', '"', '');

            if ($headers === false || $headers === [null]) {
                return $this->errorResult('O arquivo CSV está vazio.');
            }

            $headers = array_map(
                fn ($header) => $this->normalizeHeader((string) $header),
                $headers
            );

            if (count($headers) !== count(array_unique($headers))) {
                return $this->errorResult('O arquivo possui cabeçalhos duplicados.');
            }

            foreach (self::REQUIRED_HEADERS as $required) {
                if (! in_array($required, $headers, true)) {
                    return $this->errorResult(
                        "O cabeçalho obrigatório '{$required}' não foi encontrado."
                    );
                }
            }

            $allowedHeaders = array_merge(
                self::REQUIRED_HEADERS,
                self::OPTIONAL_HEADERS
            );

            foreach ($headers as $header) {
                if (! in_array($header, $allowedHeaders, true)) {
                    return $this->errorResult(
                        "O cabeçalho '{$header}' não é reconhecido."
                    );
                }
            }

            $workspaceId = (int) app('workspace_id');
            $rows = [];
            $validatedRows = [];
            $lineNumber = 1;

            while (($values = fgetcsv($handle, 0, ';', '"', '')) !== false) {
                $lineNumber++;

                if ($values === [null] || $values === []) {
                    continue;
                }

                if (count($rows) >= self::MAX_ROWS) {
                    return $this->errorResult(
                        'O arquivo ultrapassou o limite de 500 tarefas.'
                    );
                }

                if (count($values) > count($headers)) {
                    $rows[] = [
                        'row' => $lineNumber,
                        'nome' => null,
                        'valid' => false,
                        'errors' => [
                            'A linha possui mais colunas do que o cabeçalho.',
                        ],
                    ];

                    continue;
                }

                $values = array_pad($values, count($headers), null);
                $data = array_combine($headers, $values);

                $data['descricao'] = $this->nullableValue($data['descricao'] ?? null);
                $data['ID_projeto'] = $this->nullableValue($data['ID_projeto'] ?? null);
                $data['ID_equipe'] = $this->nullableValue($data['ID_equipe'] ?? null);

                $data['matriculas_colaboradores'] = $this->parseList(
                    $data['matriculas_colaboradores'] ?? null
                );

                $data['subtarefas'] = $this->parseList(
                    $data['subtarefas'] ?? null
                );

                $validator = Validator::make($data, [
                    'nome' => ['required', 'string', 'max:255'],
                    'descricao' => ['nullable', 'string'],
                    'prioridade' => ['required', 'in:alta,media,baixa'],
                    'data_prazo' => ['required', 'date_format:Y-m-d'],
                    'ID_projeto' => ['nullable', 'integer', 'min:1'],
                    'ID_equipe' => ['nullable', 'integer', 'min:1'],
                    'matriculas_colaboradores' => ['array'],
                    'subtarefas' => ['array'],
                ]);

                $errors = [];

                if ($validator->fails()) {
                    $errors = $validator->errors()->all();
                }

                if (empty($data['ID_projeto']) && empty($data['ID_equipe'])) {
                    $errors[] = 'Informe um projeto ou uma equipe para a tarefa.';
                }

                if (empty($errors)) {
                    $errors = array_merge(
                        $errors,
                        $this->validateWorkspaceRelations($data, $workspaceId)
                    );
                }

                $rowInfo = [
                    'row' => $lineNumber,
                    'nome' => $data['nome'] ?? null,
                    'valid' => empty($errors),
                    'errors' => $errors,
                ];

                $rows[] = $rowInfo;

                if (empty($errors)) {
                    $validatedRows[] = $data;
                }
            }

            $invalidCount = count(array_filter(
                $rows,
                fn ($row) => ! $row['valid']
            ));

            return [
                'valid' => ! empty($rows) && $invalidCount === 0,
                'total_rows' => count($rows),
                'valid_rows' => count($validatedRows),
                'invalid_rows' => $invalidCount,
                'rows' => $rows,
                'errors' => [],
                '_validated_rows' => $validatedRows,
            ];
        } finally {
            fclose($handle);
        }
    }

    private function validateWorkspaceRelations(
        array $data,
        int $workspaceId
    ): array {
        $errors = [];

        $project = null;
        $team = null;

        if (! empty($data['ID_projeto'])) {
            $project = Projeto::query()->find($data['ID_projeto']);

            if (! $project) {
                $errors[] = 'O projeto informado não existe.';
            } elseif (! $project->equipes()
                ->where('equipe.workspace_id', $workspaceId)
                ->exists()) {
                $errors[] = 'O projeto não pertence ao workspace ativo.';
            }
        }

        if (! empty($data['ID_equipe'])) {
            $team = Equipe::query()
                ->where('workspace_id', $workspaceId)
                ->find($data['ID_equipe']);

            if (! $team) {
                $errors[] = 'A equipe não existe ou não pertence ao workspace ativo.';
            }
        }

        if ($project && $team) {
            $belongsToProject = $project->equipes()
                ->where('equipe.ID_equipe', $team->ID_equipe)
                ->exists();

            if (! $belongsToProject) {
                $errors[] = 'A equipe informada não está associada ao projeto.';
            }
        }

        $matriculas = $data['matriculas_colaboradores'];

        if (! empty($matriculas)) {
            $existingEmployees = Funcionario::query()
                ->whereIn('matricula_funcionario', $matriculas)
                ->pluck('matricula_funcionario')
                ->map(fn ($value) => (string) $value)
                ->all();

            $workspaceEmployees = DB::table('workspace_funcionario')
                ->where('workspace_id', $workspaceId)
                ->whereIn('matricula_funcionario', $matriculas)
                ->pluck('matricula_funcionario')
                ->map(fn ($value) => (string) $value)
                ->all();

            foreach ($matriculas as $matricula) {
                if (! in_array((string) $matricula, $existingEmployees, true)) {
                    $errors[] = "O colaborador {$matricula} não foi encontrado.";
                } elseif (! in_array((string) $matricula, $workspaceEmployees, true)) {
                    $errors[] = "O colaborador {$matricula} não pertence ao workspace ativo.";
                }
            }
        }

        return $errors;
    }

    private function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('trim', explode('|', $value)),
            fn ($item) => $item !== ''
        )));
    }

    private function openCsv(UploadedFile $file)
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException(
                'Não foi possível abrir o arquivo CSV.'
            );
        }

        return $handle;
    }

    private function normalizeHeader(string $header): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $header));
    }

    private function nullableValue(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return trim((string) $value);
    }

    private function errorResult(string $message): array
    {
        return [
            'valid' => false,
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'rows' => [],
            'errors' => [$message],
            '_validated_rows' => [],
        ];
    }
}