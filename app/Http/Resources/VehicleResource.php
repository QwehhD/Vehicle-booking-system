<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
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
            'plate_number' => $this->plate_number,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'ownership' => $this->ownership->value,
            'ownership_label' => $this->ownership->label(),
            'rental_company' => $this->rental_company,
            'odometer' => $this->odometer,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'last_service_at' => $this->last_service_at?->toDateString(),
            'next_service_at' => $this->next_service_at?->toDateString(),
            'days_until_service' => $this->next_service_at === null
                ? null
                : (int) today()->diffInDays($this->next_service_at, false),
            'completed_bookings_count' => $this->whenCounted('completed_bookings'),
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'region' => $this->location->relationLoaded('region')
                    ? ['id' => $this->location->region->id, 'name' => $this->location->region->name]
                    : null,
            ]),
        ];
    }
}
