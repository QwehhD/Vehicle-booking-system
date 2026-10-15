<?php

namespace App\Support;

/**
 * Pencarian LIKE yang memperlakukan input sebagai teks biasa: `%` dan `_`
 * dari pengguna tidak menjadi wildcard. Karakter escape `!` dipilih karena
 * perilakunya sama di SQLite dan MySQL/MariaDB, tidak seperti backslash.
 */
final class Like
{
    private const ESCAPE = '!';

    /**
     * Pola "mengandung" untuk dipakai sebagai binding klausa dari clause().
     */
    public static function contains(string $term): string
    {
        $escaped = str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $term,
        );

        return "%{$escaped}%";
    }

    /**
     * Klausa `kolom LIKE ?` beserta karakter escape-nya. Kolom harus berupa
     * nama kolom tetap dari kode, bukan input pengguna.
     *
     * @param  literal-string  $column
     * @return literal-string
     */
    public static function clause(string $column): string
    {
        return "{$column} like ? escape '".self::ESCAPE."'";
    }
}
