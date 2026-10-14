<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VehicleType: string
{
    use HasOptions;

    case Passenger = 'passenger';
    case Cargo = 'cargo';

    public function label(): string
    {
        return match ($this) {
            self::Passenger => 'Angkutan Orang',
            self::Cargo => 'Angkutan Barang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Passenger => 'blue',
            self::Cargo => 'violet',
        };
    }
}
