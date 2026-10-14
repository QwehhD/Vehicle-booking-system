<?php

namespace App\Enums\Concerns;

/**
 * Daftar pilihan enum untuk select di frontend.
 *
 * @phpstan-require-implements \BackedEnum
 */
trait HasOptions
{
    abstract public function label(): string;

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => (string) $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
