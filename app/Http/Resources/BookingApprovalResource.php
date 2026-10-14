<?php

namespace App\Http\Resources;

use App\Models\BookingApproval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BookingApproval
 */
class BookingApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level' => $this->level,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'note' => $this->note,
            'acted_at' => $this->acted_at?->toIso8601String(),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
                'position' => $this->approver->position,
            ]),
            'booking' => BookingResource::make($this->whenLoaded('booking')),
            'can_process' => $this->when(
                $this->relationLoaded('booking'),
                fn () => (bool) $request->user()?->can('approve', $this->resource),
            ),
        ];
    }
}
