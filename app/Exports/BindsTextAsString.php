<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Menyimpan setiap teks sebagai string. Tanpa ini, isian pengguna yang
 * diawali "=" (mis. `=HYPERLINK(...)`) tersimpan sebagai rumus dan ikut
 * dijalankan saat file dibuka (formula injection). Angka dan tanggal tetap
 * memakai pengikat bawaan.
 */
trait BindsTextAsString
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }
}
