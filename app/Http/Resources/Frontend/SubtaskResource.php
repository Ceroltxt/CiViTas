<?php

namespace App\Http\Resources\Frontend;

use App\Models\Task\Subtarefa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subtarefa */
class SubtaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->ID_subtarefa,
            'title' => $this->nome,
            'completed' => $this->concluida,
            'assignee' => $this->when(
                $this->relationLoaded('colaborador') && $this->colaborador,
                fn () => new UserSummaryResource($this->colaborador),
            ),
            'dueDate' => $this->data_prazo?->format('d/m/y'),
        ];
    }
}
