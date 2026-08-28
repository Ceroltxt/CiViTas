<?php

namespace App\Http\Resources\Frontend;

use App\Models\Task\HistoricoTarefa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HistoricoTarefa */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->ID_historico,
            'date' => $this->created_at?->format('d/m/Y') ?? '',
            'user' => $this->funcionario
                ? trim("{$this->funcionario->nome} {$this->funcionario->sobrenome}")
                : 'Sistema',
            'action' => $this->acao,
            'details' => $this->detalhes ?? '',
            'language' => '---',
        ];
    }
}
