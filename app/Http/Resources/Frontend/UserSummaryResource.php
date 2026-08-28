<?php

namespace App\Http\Resources\Frontend;

use App\Models\Identity\Funcionario;
use App\Support\Frontend\AppRoleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Funcionario */
class UserSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->matricula_funcionario,
            'name' => trim("{$this->nome} {$this->sobrenome}"),
            'role' => AppRoleResolver::resolveFromFuncionario($this->resource),
            'avatar' => null,
        ];
    }
}
