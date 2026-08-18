<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $connected = false;

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            $connected = true;
        } catch (Throwable) {
            $connected = false;
        }

        return response()->json([
            'name' => config('app.name'),
            'environment' => app()->environment(),
            'status' => 'ok',
            'database' => [
                'connection' => config('database.default'),
                'connected' => $connected,
            ],
        ]);
    }
}
