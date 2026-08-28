<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Identity\Funcionario;
use App\Support\Frontend\AppRoleResolver;
use App\Support\Frontend\NavigationBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Funcionario $funcionario */
        $funcionario = $request->user();
        $funcionario->load('cargo');

        return response()->json(
            NavigationBuilder::forRole(AppRoleResolver::appRoleKey($funcionario)),
        );
    }
}
