<?php

namespace App\Enums;

enum VehicleType: string
{
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
