<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Gaya sel yang dipakai bersama oleh sheet laporan.
 */
final class ReportStyles
{
    /**
     * Header tabel: tebal, teks putih di atas latar biru tua.
     *
     * @var array<string, mixed>
     */
    public const HEADER = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
    ];

    /**
     * @var array<string, mixed>
     */
    public const TITLE = [
        'font' => ['bold' => true, 'size' => 14],
    ];

    /**
     * @var array<string, mixed>
     */
    public const LABEL = [
        'font' => ['bold' => true],
    ];
}
