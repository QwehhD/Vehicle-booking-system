<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Aksi yang dicatat ke log aktivitas.
 */
enum ActivityAction: string
{
    use HasOptions;

    case Login = 'auth.login';
    case Logout = 'auth.logout';
    case LoginFailed = 'auth.failed';
    case BookingCreated = 'booking.created';
    case BookingCancelled = 'booking.cancelled';
    case BookingCompleted = 'booking.completed';
    case ApprovalApproved = 'approval.approved';
    case ApprovalRejected = 'approval.rejected';
    case ReportExported = 'report.exported';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Masuk',
            self::Logout => 'Keluar',
            self::LoginFailed => 'Gagal masuk',
            self::BookingCreated => 'Pemesanan dibuat',
            self::BookingCancelled => 'Pemesanan dibatalkan',
            self::BookingCompleted => 'Pemesanan selesai',
            self::ApprovalApproved => 'Persetujuan disetujui',
            self::ApprovalRejected => 'Persetujuan ditolak',
            self::ReportExported => 'Laporan diekspor',
        };
    }

    /**
     * Warna badge, selaras dengan warna status pemesanan dan approval.
     */
    public function color(): string
    {
        return match ($this) {
            self::Login, self::Logout, self::BookingCancelled => 'slate',
            self::LoginFailed, self::ApprovalRejected => 'red',
            self::BookingCreated => 'violet',
            self::BookingCompleted => 'emerald',
            self::ApprovalApproved => 'blue',
            self::ReportExported => 'orange',
        };
    }
}
