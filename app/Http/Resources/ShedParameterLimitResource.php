<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ShedParameterLimitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'shed_parameter_limit',
            'id' => $this->id,
            'attributes' => [
                'shed_id' => $this->shed_id,
                'parameter_name' => $this->parameter_name,
                'unit' => $this->unit,
                'min_value' => $this->min_value,
                'max_value' => $this->max_value,
                'avg_value' => $this->avg_value,
                'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
                'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,
            ],
        ];
    }
}
