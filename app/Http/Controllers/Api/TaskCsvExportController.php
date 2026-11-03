<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task\Tarefa;
use App\Support\Export\TaskCsvExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TaskCsvExportController extends Controller
{
    public function __invoke(Request $request, TaskCsvExporter $exporter)
    {
        Gate::authorize('viewAny', Tarefa::class);

        if (! app()->bound('workspace_id')) {
            abort(403, 'Workspace ativo não identificado.');
        }

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