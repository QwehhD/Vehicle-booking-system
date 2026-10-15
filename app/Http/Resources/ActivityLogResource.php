<?php

namespace App\Http\Resources;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // IP dan user agent hanya untuk admin; approver yang melihat riwayat
        // pemesanan tidak perlu tahu perangkat dan jaringan pengguna lain.
        $isAdmin = $request->user()?->isAdmin() ?? false;

        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => ActivityAction::tryFrom($this->action)?->label() ?? $this->action,
            'action_color' => ActivityAction::tryFrom($this->action)?->color() ?? 'slate',
            'description' => $this->description,
            'properties' => $this->properties,
            'ip_address' => $isAdmin ? $this->ip_address : null,
            'user_agent' => $isAdmin ? $this->user_agent : null,
            'created_at' => $this->created_at->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'booking' => $this->whenLoaded('subject', fn () => $this->subject instanceof Booking
                ? ['id' => $this->subject->id, 'code' => $this->subject->code]
                : null),
        ];
    }
}
