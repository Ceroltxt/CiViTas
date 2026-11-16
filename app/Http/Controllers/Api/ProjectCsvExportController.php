<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project\Projeto;
use App\Support\Export\ProjectCsvExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectCsvExportController extends Controller
{
    public function __invoke(Request $request, ProjectCsvExporter $exporter)
    {
        // Verifica se o usuário pode visualizar projetos.
        Gate::authorize('viewAny', Projeto::class);

        // Garante que exista um workspace ativo.
        if (! app()->bound('workspace_id')) {
            abort(403, 'Workspace ativo não identificado.');
        }

        // Valida o período solicitado.
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
        ]);

        return $exporter->download(
            $validated['start_date'],
            $validated['end_date']
        );
    }
}
