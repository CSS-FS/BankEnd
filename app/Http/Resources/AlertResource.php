<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'alert',
            'id' => $this->id,
            'attributes' => [
                'title' => $this->title,
                'message' => $this->message,
                'type' => $this->type,
                'severity' => $this->severity,
                'channel' => $this->channel,
                'data' => $this->data,

                // State/Lifecycle
                'status' => $this->status,
                'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
                'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
                'is_read' => (bool) $this->is_read,
                'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
                'is_dismissed' => (bool) $this->is_dismissed,
                'dismissed_at' => $this->dismissed_at?->format('Y-m-d H:i:s'),
                'created_at' => $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null,
                'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null,

                // Foreign Ids
                'user_id' => $this->user_id,
                'farm_id' => $this->farm_id,
                'shed_id' => $this->shed_id,
                'flock_id' => $this->flock_id,

                // Relationships (loaded when needed)
                'user' => $this->whenLoaded('user', fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ]),
                'farm' => $this->whenLoaded('farm', fn () => [
                    'id' => $this->farm->id,
                    'name' => $this->farm->name,
                ]),
                'shed' => $this->whenLoaded('shed', fn () => [
                    'id' => $this->shed->id,
                    'name' => $this->shed->name,
                ]),
                'flock' => $this->whenLoaded('flock', fn () => [
                    'id' => $this->flock->id,
                    'name' => $this->flock->name,
                ]),

                // Responses (included when showing single alert)
                'responses' => AlertResponseResource::collection(
                    $this->whenLoaded('responses')
                ),
                'responses_count' => $this->whenCounted('responses'),

                // Latest response summary
                'latest_response' => $this->whenLoaded('latestResponse', function () {
                    return $this->latestResponse ? new AlertResponseResource($this->latestResponse) : null;
                }),

                // Helper methods for frontend
                'is_critical' => $this->severity === 'fatal',
                'is_unread' => ! $this->is_read,
                'age_in_hours' => $this->created_at ? $this->created_at->diffForHumans() : null,
            ],
            // Links
            'links' => [
                'self' => route('alerts.show', $this->id),
                'mark_as_read' => route('alerts.mark-read', $this->id),
                'dismiss' => route('alerts.dismiss', $this->id),
                'un_dismiss' => route('alerts.undismiss', $this->id),
                'respond' => route('alert.responses.store', $this->id),
            ],
        ];
    }

    /**
     * Customize the response for the resource.
     */
    public function withResponse($request, $response)
    {
        $response->header('X-Alert-Severity', $this->severity);
        $response->header('X-Alert-Type', $this->type);
    }
}
