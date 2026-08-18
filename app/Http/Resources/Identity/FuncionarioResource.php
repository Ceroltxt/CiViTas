<?php

namespace App\Http\Resources\Identity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Identity\Funcionario
 */
class FuncionarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'matricula' => $this->matricula_funcionario,
            'nome' => $this->nome,
            'sobrenome' => $this->sobrenome,
            'email' => $this->email,
            'pontos_totais' => $this->pontos_totais,
            'departamento' => $this->whenLoaded('departamento', fn () => [
                'id' => $this->departamento?->ID_departamento,
                'nome' => $this->departamento?->nome_departamento,
            ]),
            'cargo' => $this->whenLoaded('cargo', fn () => [
                'id' => $this->cargo?->ID_cargo,
                'nome' => $this->cargo?->nome_cargo,
            ]),
        ];
    }
}
