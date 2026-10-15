<?php

namespace App\Services;

use App\Exports\BookingReportExport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * Mengunduh laporan Excel dan mencatat filter yang dipakai.
     */
    public function download(User $admin, BookingReportQuery $report): BinaryFileResponse
    {
        $from = $report->filters['date_from'];
        $to = $report->filters['date_to'];

        $this->activityLogger->log(
            'report.exported',
            null,
            sprintf(
                '%s mengekspor laporan pemesanan %s – %s',
                $admin->name,
                CarbonImmutable::parse($from)->translatedFormat('j M Y'),
                CarbonImmutable::parse($to)->translatedFormat('j M Y'),
            ),
            $report->filters,
        );

        return Excel::download(
            new BookingReportExport($report, $admin, now()),
            "laporan-pemesanan_{$from}_{$to}.xlsx",
        );
    }
}
