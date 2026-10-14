<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $approvalsCount = $this->resource->getAttribute('approvals_count');
        $approvedCount = $this->resource->getAttribute('approved_approvals_count');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'requester_name' => $this->requester_name,
            'requester_department' => $this->requester_department,
            'purpose' => $this->purpose,
            'destination' => $this->destination,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'current_level' => $this->current_level,
            'approval_progress' => $this->when(
                $approvalsCount !== null && $approvedCount !== null,
                fn () => ['approved' => (int) $approvedCount, 'total' => (int) $approvalsCount],
            ),
            'odometer_start' => $this->odometer_start,
            'odometer_end' => $this->odometer_end,
            'distance' => $this->odometer_start !== null && $this->odometer_end !== null
                ? $this->odometer_end - $this->odometer_start
                : null,
            'fuel_used' => $this->fuel_used === null ? null : (float) $this->fuel_used,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'driver' => DriverResource::make($this->whenLoaded('driver')),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'approvals' => BookingApprovalResource::collection($this->whenLoaded('approvals')),
        ];
    }
}
