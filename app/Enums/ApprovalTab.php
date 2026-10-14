<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Tab pada inbox persetujuan approver.
 */
enum ApprovalTab: string
{
    use HasOptions;

    case Waiting = 'waiting';
    case Queue = 'queue';
    case History = 'history';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Menunggu Saya',
            self::Queue => 'Antrian',
            self::History => 'Riwayat',
        };
    }
}
