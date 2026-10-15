<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VehicleOwnership: string
{
    use HasOptions;

    case Owned = 'owned';
    case Rented = 'rented';

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'Milik Perusahaan',
            self::Rented => 'Sewa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Owned => 'slate',
            self::Rented => 'orange',
        };
    }
}
