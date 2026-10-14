<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'blue',
            self::Rejected => 'red',
            self::Cancelled => 'slate',
            self::Completed => 'emerald',
        };
    }

    /**
     * Status yang masih memakai jadwal kendaraan dan driver.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Approved];
    }
}
