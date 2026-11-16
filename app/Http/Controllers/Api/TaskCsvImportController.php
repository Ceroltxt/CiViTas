<?php

namespace App\Http\Controllers\Api;

use App\Domain\Authorization\Permissions;
use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use App\Support\Import\TaskCsvImporter;
use Illuminate\Http\Request;

class TaskCsvImportController extends Controller
{
    public function preview(Request $request, TaskCsvImporter $importer)
    {
        $this->authorizeImport($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importer->preview($validated['file']);

        return response()->json(
            $result,
            ($result['valid'] ?? false) ? 200 : 422
        );
    }

    public function confirm(Request $request, TaskCsvImporter $importer)
    {
        $funcionario = $this->authorizeImport($request);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importer->confirm(
            $validated['file'],
            $funcionario
        );

        return response()->json(
            $result,
            ($result['valid'] ?? false) ? 201 : 422
        );
    }

    private function authorizeImport(Request $request): Funcionario
    {
        $funcionario = $request->user();

        abort_unless(
            $funcionario instanceof Funcionario,
            403,
            'Usuário não autorizado.'
        );

        abort_unless(
            $funcionario->hasPermission(Permissions::TASKS_CREATE),
            403,
            'Você não tem permissão para importar tarefas compartilhadas.'
        );

        abort_unless(
            app()->bound('workspace_id'),
            403,
            'Workspace ativo não identificado.'
        );

        return $funcionario;
    }
}
