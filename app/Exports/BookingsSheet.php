<?php

namespace App\Exports;

use App\Enums\ApprovalStatus;
use App\Models\Booking;
use App\Models\BookingApproval;
use App\Services\BookingReportQuery;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithFreezePane;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet detail: satu baris per pemesanan, header beku dan ber-autofilter.
 */
class BookingsSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithFreezePane, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles, WithTitle
{
    private const DATE_TIME_FORMAT = 'dd/mm/yyyy hh:mm';

    private int $rowNumber = 0;

    public function __construct(private BookingReportQuery $report) {}

    /**
     * @return Enumerable<int, Booking>
     */
    public function collection(): Enumerable
    {
        return $this->report->detailed()->get();
    }

    public function title(): string
    {
        return 'Pemesanan';
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Kode',
            'Tanggal Dibuat',
            'Pemohon',
            'Departemen',
            'Keperluan',
            'Tujuan',
            'Plat',
            'Kendaraan',
            'Jenis',
            'Kepemilikan',
            'Lokasi Kendaraan',
            'Region',
            'Driver',
            'Mulai',
            'Selesai',
            'Status',
            'Progres Persetujuan',
            'Detail Persetujuan',
            'Km Tempuh',
            'BBM (L)',
        ];
    }

    /**
     * @return list<mixed>
     */
    public function map(mixed $booking): array
    {
        if (! $booking instanceof Booking) {
            throw new InvalidArgumentException('Baris laporan harus berupa pemesanan.');
        }

        $approvals = $booking->approvals;

        return [
            ++$this->rowNumber,
            $booking->code,
            ExcelDate::dateTimeToExcel($booking->created_at),
            $booking->requester_name,
            $booking->requester_department,
            $booking->purpose,
            $booking->destination,
            $booking->vehicle->plate_number,
            $booking->vehicle->name,
            $booking->vehicle->type->label(),
            $booking->vehicle->ownership->label(),
            $booking->vehicle->location->name,
            $booking->vehicle->location->region->name,
            $booking->driver->name,
            ExcelDate::dateTimeToExcel($booking->start_at),
            ExcelDate::dateTimeToExcel($booking->end_at),
            $booking->status->label(),
            $approvals->where('status', ApprovalStatus::Approved)->count().'/'.$approvals->count(),
            $approvals->map(fn (BookingApproval $approval) => $this->describeApproval($approval))->implode('; '),
            $booking->odometer_start !== null && $booking->odometer_end !== null
                ? $booking->odometer_end - $booking->odometer_start
                : null,
            $booking->fuel_used === null ? null : (float) $booking->fuel_used,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => self::DATE_TIME_FORMAT,
            'O' => self::DATE_TIME_FORMAT,
            'P' => self::DATE_TIME_FORMAT,
            'T' => '#,##0',
            'U' => '#,##0.00',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): ?array
    {
        return [1 => ReportStyles::HEADER];
    }

    public function freezePane(): string
    {
        return 'A2';
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->getSheet()->getDelegate();
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
            },
        ];
    }

    /**
     * Mis. "L1: Budi Santoso – Disetujui 12/10 09:00".
     */
    private function describeApproval(BookingApproval $approval): string
    {
        $description = "L{$approval->level}: {$approval->approver->name} – {$approval->status->label()}";

        return $approval->acted_at === null
            ? $description
            : $description.' '.$approval->acted_at->format('d/m H:i');
    }
}
