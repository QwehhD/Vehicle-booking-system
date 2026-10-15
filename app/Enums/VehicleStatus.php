<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VehicleStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Maintenance => 'Perawatan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'emerald',
            self::Maintenance => 'amber',
        };
    }
}
