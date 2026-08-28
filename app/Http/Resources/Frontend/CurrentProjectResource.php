<?php

namespace App\Http\Resources\Frontend;

use App\Models\Project\Projeto;
use App\Support\Frontend\ProjectProgressCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Projeto */
class CurrentProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->nome,
            'deadline' => $this->data_previsao_fim?->locale('pt_BR')->translatedFormat('d \d\e M. Y') ?? '—',
            'progress' => ProjectProgressCalculator::forProject($this->resource),
        ];
    }
}
