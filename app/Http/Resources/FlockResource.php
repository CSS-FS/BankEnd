<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'flock',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'start_date' => $this->start_date ? Carbon::parse($this->start_date)->format('Y-m-d') : null,
                'end_date' => $this->end_date ? Carbon::parse($this->end_date)->format('Y-m-d') : null,
                'age' => $this->age,
		'breed_id' => $this->breed_id,
		'chicken_count' => $this->chicken_count,
                'status' => $this->status,
                'live_bird_count' => $this->live_bird_count,
                'daily_mortality' => $this->daily_mortality,
                'weekly_mortality' => $this->weekly_mortality,
                'all_time_mortality' => $this->all_time_mortality,
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

                'weight' => [
                    'avg_weight' => $this->latest_weight_log['avg_weight'] ?? 0,
                    'daily_gain' => $this->latest_weight_log['avg_weight_gain'] ?? 0,
                    'fcr' => $this->latest_weight_log['feed_conversion_ratio'] ?? 0,
                    'record_time' => $this->latest_weight_log['created_at'] ?? null,
                ],

                $this->mergeWhen($this->relationLoaded('shed'), [
                    'shed' => [
                        'id' => $this->shed->id,
                        'name' => $this->shed->name,
                        'capacity' => $this->shed->capacity,
                        'type' => $this->shed->type,
                    ],
                ]),

                $this->mergeWhen($this->relationLoaded('breed'), [
                    'breed' => [
                        'id' => $this->breed->id,
                        'name' => $this->breed->name,
                    ],
                ]),
            ],
        ];
    }
}
