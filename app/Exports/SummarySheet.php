<?php

namespace App\Exports;

use App\Enums\BookingStatus;
use App\Enums\VehicleType;
use App\Models\Region;
use App\Models\User;
use App\Services\BookingReportQuery;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet ringkasan: periode, pencetak, jumlah per status, total km & BBM,
 * dan lima kendaraan yang paling sering dipakai.
 */
class SummarySheet implements FromArray, ShouldAutoSize, WithEvents, WithStrictNullComparison, WithStyles, WithTitle
{
    /**
     * @var list<list<mixed>>
     */
    private array $rows = [];

    /**
     * Nomor baris (1-based) header tabel.
     *
     * @var list<int>
     */
    private array $headerRows = [];

    /**
     * Nomor baris (1-based) yang labelnya ditebalkan.
     *
     * @var list<int>
     */
    private array $labelRows = [];

    /**
     * Rentang sel per format angka.
     *
     * @var array<string, list<string>>
     */
    private array $numberFormats = ['#,##0' => [], '#,##0.00' => []];

    public function __construct(
        private BookingReportQuery $report,
        private User $printedBy,
        private CarbonImmutable $printedAt,
    ) {}

    public function title(): string
    {
        return 'Ringkasan';
    }

    /**
     * @return list<list<mixed>>
     */
    public function array(): array
    {
        $filters = $this->report->filters;
        $summary = $this->report->summary();
        $this->rows = $this->headerRows = $this->labelRows = [];
        $this->numberFormats = ['#,##0' => [], '#,##0.00' => []];

        $this->addRow(['Laporan Pemesanan Kendaraan']);
        $this->addRow(['']);
        $this->addLabelRow('Periode', $this->formatDate($filters['date_from']).' – '.$this->formatDate($filters['date_to']));
        $this->addLabelRow('Status', $filters['status'] ? BookingStatus::from($filters['status'])->label() : 'Semua');
        $this->addLabelRow('Jenis kendaraan', $filters['vehicle_type'] ? VehicleType::from($filters['vehicle_type'])->label() : 'Semua');
        $this->addLabelRow('Region', $filters['region_id'] ? (string) Region::query()->whereKey($filters['region_id'])->value('name') : 'Semua');
        $this->addLabelRow('Dicetak oleh', $this->printedBy->name);
        $this->addLabelRow('Waktu cetak', $this->printedAt->translatedFormat('j M Y, H:i'));
        $this->addRow(['']);

        $this->headerRows[] = $this->addRow(['Status', 'Jumlah']);
        $firstStatusRow = count($this->rows) + 1;
        foreach (BookingStatus::cases() as $status) {
            $this->addRow([$status->label(), $summary['by_status'][$status->value]]);
        }
        $totalRow = $this->addLabelRow('Total', $summary['total']);
        $this->numberFormats['#,##0'][] = "B{$firstStatusRow}:B{$totalRow}";
        $this->addRow(['']);

        $distanceRow = $this->addLabelRow('Total km tempuh', $summary['distance']);
        $fuelRow = $this->addLabelRow('Total BBM (L)', $summary['fuel']);
        $this->numberFormats['#,##0'][] = "B{$distanceRow}";
        $this->numberFormats['#,##0.00'][] = "B{$fuelRow}";
        $this->addRow(['']);

        $this->labelRows[] = $this->addRow(['5 kendaraan paling sering dipakai']);
        $this->headerRows[] = $this->addRow(['No', 'Plat', 'Kendaraan', 'Pemakaian', 'Km Tempuh']);
        $topVehicles = $this->report->topVehicles(5);
        $firstVehicleRow = count($this->rows) + 1;

        if ($topVehicles === []) {
            $this->addRow(['–', 'Belum ada pemakaian kendaraan pada periode ini.']);
        }

        foreach ($topVehicles as $index => $vehicle) {
            $this->addRow([$index + 1, $vehicle['plate_number'], $vehicle['name'], $vehicle['bookings'], $vehicle['distance']]);
        }

        $this->numberFormats['#,##0'][] = "D{$firstVehicleRow}:E".count($this->rows);

        return $this->rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): ?array
    {
        $styles = [1 => ReportStyles::TITLE];

        foreach ($this->labelRows as $row) {
            $styles[$row] = ReportStyles::LABEL;
        }

        return $styles;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->getSheet()->getDelegate();

                foreach ($this->headerRows as $row) {
                    $lastColumn = $sheet->getHighestDataColumn($row);
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray(ReportStyles::HEADER);
                }

                foreach ($this->numberFormats as $format => $ranges) {
                    foreach ($ranges as $range) {
                        $sheet->getStyle($range)->getNumberFormat()->setFormatCode($format);
                    }
                }
            },
        ];
    }

    /**
     * Menambahkan satu baris dan mengembalikan nomor barisnya (1-based).
     *
     * @param  array<int, mixed>  $cells
     */
    private function addRow(array $cells): int
    {
        $this->rows[] = array_values($cells);

        return count($this->rows);
    }

    private function addLabelRow(string $label, mixed $value): int
    {
        $row = $this->addRow([$label, $value]);
        $this->labelRows[] = $row;

        return $row;
    }

    private function formatDate(string $date): string
    {
        return CarbonImmutable::parse($date)->translatedFormat('j M Y');
    }
}
