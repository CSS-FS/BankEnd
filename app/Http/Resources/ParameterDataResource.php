<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'parameter' => $this->resource['parameter'] ?? null,
            'current_value' => $this->resource['current_value'] ?? null,
            'unit' => $this->resource['unit'] ?? null,
            'statistics' => [
                'min' => $this->resource['min'] ?? null,
                'average' => $this->resource['avg'] ?? null,
                'max' => $this->resource['max'] ?? null,
            ],
            'chart_data' => $this->resource['chart_data'] ?? [],
            'alert_thresholds' => $this->resource['alert_thresholds'] ?? null,
        ];
    }
}
