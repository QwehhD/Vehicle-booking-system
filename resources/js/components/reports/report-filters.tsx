import { router } from '@inertiajs/react';
import { FilterSelect } from '@/components/filter-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/reports';
import type { Option, ReportFilters as Filters } from '@/types';

type Range = { date_from: string; date_to: string };

/**
 * Tanggal hari ini menurut WIB, terlepas dari zona waktu browser.
 */
function todayInJakarta(): { year: number; month: number } {
    const [year, month] = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
    })
        .format(new Date())
        .split('-')
        .map(Number);

    return { year, month };
}

function isoDate(year: number, month: number, day: number): string {
    const date = new Date(Date.UTC(year, month - 1, day));

    return date.toISOString().slice(0, 10);
}

/**
 * Rentang dari awal bulan `fromMonthOffset` sampai akhir bulan `toMonthOffset`,
 * relatif terhadap bulan berjalan.
 */
function monthRange(fromMonthOffset: number, toMonthOffset: number): Range {
    const { year, month } = todayInJakarta();

    return {
        date_from: isoDate(year, month + fromMonthOffset, 1),
        date_to: isoDate(year, month + toMonthOffset + 1, 0),
    };
}

function presets(): { label: string; range: Range }[] {
    const { year } = todayInJakarta();

    return [
        { label: 'Bulan ini', range: monthRange(0, 0) },
        { label: 'Bulan lalu', range: monthRange(-1, -1) },
        { label: '3 bulan terakhir', range: monthRange(-2, 0) },
        {
            label: 'Tahun ini',
            range: { date_from: `${year}-01-01`, date_to: `${year}-12-31` },
        },
    ];
}

function withoutEmpty(filters: Filters): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== null),
    ) as Record<string, string | number>;
}

export function ReportFilters({
    filters,
    statuses,
    vehicleTypes,
    regions,
}: {
    filters: Filters;
    statuses: Option[];
    vehicleTypes: Option[];
    regions: { id: number; name: string }[];
}) {
    const apply = (changes: Partial<Filters>) => {
        router.get(index.url(), withoutEmpty({ ...filters, ...changes }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div className="grid gap-4">
            <div
                className="flex flex-wrap gap-2"
                role="group"
                aria-label="Periode cepat"
            >
                {presets().map((preset) => {
                    const active =
                        preset.range.date_from === filters.date_from &&
                        preset.range.date_to === filters.date_to;

                    return (
                        <Button
                            key={preset.label}
                            type="button"
                            size="sm"
                            variant={active ? 'default' : 'outline'}
                            aria-pressed={active}
                            onClick={() => apply(preset.range)}
                        >
                            {preset.label}
                        </Button>
                    );
                })}
            </div>

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div className="grid gap-1.5">
                    <Label htmlFor="date_from">Dari tanggal</Label>
                    <Input
                        id="date_from"
                        type="date"
                        required
                        value={filters.date_from}
                        max={filters.date_to}
                        onChange={(event) =>
                            event.target.value &&
                            apply({ date_from: event.target.value })
                        }
                    />
                </div>
                <div className="grid gap-1.5">
                    <Label htmlFor="date_to">Sampai tanggal</Label>
                    <Input
                        id="date_to"
                        type="date"
                        required
                        value={filters.date_to}
                        min={filters.date_from}
                        onChange={(event) =>
                            event.target.value &&
                            apply({ date_to: event.target.value })
                        }
                    />
                </div>
                <FilterSelect
                    id="status"
                    label="Status"
                    value={filters.status}
                    options={statuses}
                    onChange={(status) =>
                        apply({ status: status as Filters['status'] })
                    }
                />
                <FilterSelect
                    id="vehicle_type"
                    label="Jenis kendaraan"
                    value={filters.vehicle_type}
                    options={vehicleTypes}
                    onChange={(type) =>
                        apply({ vehicle_type: type as Filters['vehicle_type'] })
                    }
                />
                <FilterSelect
                    id="region_id"
                    className="col-span-2 lg:col-span-1"
                    label="Region"
                    value={filters.region_id?.toString() ?? null}
                    options={regions.map((region) => ({
                        value: region.id.toString(),
                        label: region.name,
                    }))}
                    onChange={(region) =>
                        apply({ region_id: region ? Number(region) : null })
                    }
                />
            </div>
        </div>
    );
}
