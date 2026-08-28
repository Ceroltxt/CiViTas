<?php

namespace App\Http\Resources\Frontend;

use App\Models\Task\HistoricoTarefa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HistoricoTarefa */
class TaskAuditEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->funcionario
            ? trim("{$this->funcionario->nome} {$this->funcionario->sobrenome}")
            : 'Sistema';

        return [
            'id' => (string) $this->ID_historico,
            'icon' => 'i-heroicons-clipboard-document-list',
            'message' => $this->acao,
            'user' => $user,
            'timestamp' => $this->created_at?->locale('pt_BR')->translatedFormat('d/m/Y \à\s H:i') ?? '',
        ];
    }
}
