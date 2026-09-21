<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use App\Support\Frontend\AppRoleResolver;
use App\Support\Frontend\NavigationBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NavigationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $funcionario->loadMissing('cargo');
        $roleKey = AppRoleResolver::appRoleKey($funcionario);

        $nav = Cache::remember("nav_role_{$roleKey}", 120, function () use ($roleKey) {
            return NavigationBuilder::forRole($roleKey);
        });

        return response()->json($nav);
    }
}
