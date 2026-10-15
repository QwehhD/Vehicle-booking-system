<?php

namespace App\Exports;

use App\Models\User;
use App\Services\BookingReportQuery;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Laporan pemesanan: sheet "Pemesanan" (detail) dan "Ringkasan".
 */
class BookingReportExport implements Export, WithMultipleSheets
{
    public function __construct(
        private BookingReportQuery $report,
        private User $printedBy,
        private CarbonImmutable $printedAt,
    ) {}

    /**
     * @return array<int, Export>
     */
    public function sheets(): array
    {
        return [
            new BookingsSheet($this->report),
            new SummarySheet($this->report, $this->printedBy, $this->printedAt),
        ];
    }
}
