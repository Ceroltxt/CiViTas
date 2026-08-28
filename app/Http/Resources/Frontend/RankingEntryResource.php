<?php

namespace App\Http\Resources\Frontend;

use App\Models\Identity\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Funcionario */
class RankingEntryResource extends JsonResource
{
    public function __construct(
        Funcionario $resource,
        private readonly int $position,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'position' => $this->position,
            'user' => new UserSummaryResource($this->resource),
            'stars' => (int) $this->pontos_totais,
        ];
    }
}
