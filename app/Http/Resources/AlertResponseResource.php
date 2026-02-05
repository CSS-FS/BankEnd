<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'alert_response',
            'id' => $this->id,
            'attributes' => [
                'alert_id' => $this->alert_id,
                'action_type' => $this->action_type,
                'action_details' => $this->action_details,

                // User references
                'creator_id' => $this->creator_id,
                'responder_id' => $this->responder_id,

                // User information
                'creator' => $this->whenLoaded('creator', fn () => [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'role' => $this->creator->roles->first()?->name,
                ]),
                'responder' => $this->whenLoaded('responder', fn () => [
                    'id' => $this->responder->id,
                    'name' => $this->responder->name,
                    'role' => $this->responder->roles->first()?->name,
                ]),
                'responded_at' => $this->responded_at?->format('Y-m-d H:i:s'),

                // Timestamps
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

                // Status helpers
                'is_resolved' => $this->action_type === 'Resolved',
                'is_acknowledged' => $this->action_type === 'Acknowledged',
                'is_escalated' => $this->action_type === 'Escalated',
            ],
            // Links
            'links' => [
                'alert' => route('alerts.show', $this->alert_id),
            ],
        ];
    }
}
