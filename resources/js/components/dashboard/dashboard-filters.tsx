import { router } from '@inertiajs/react';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { DashboardFilters as Filters } from '@/types';

const ALL = 'all';

export function DashboardFilters({
    filters,
    years,
    regions,
}: {
    filters: Filters;
    years: number[];
    regions: { id: number; name: string }[];
}) {
    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            dashboard.url(),
            {
                year: next.year,
                ...(next.region_id === null
                    ? {}
                    : { region_id: next.region_id }),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <div className="flex flex-wrap items-end gap-3">
            <div className="grid gap-1.5">
                <Label htmlFor="year">Tahun</Label>
                <Select
                    value={String(filters.year)}
                    onValueChange={(year) => apply({ year: Number(year) })}
                >
                    <SelectTrigger id="year" className="w-28">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {years.map((year) => (
                            <SelectItem key={year} value={String(year)}>
                                {year}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="grid gap-1.5">
                <Label htmlFor="region_id">Region</Label>
                <Select
                    value={filters.region_id?.toString() ?? ALL}
                    onValueChange={(region) =>
                        apply({
                            region_id: region === ALL ? null : Number(region),
                        })
                    }
                >
                    <SelectTrigger id="region_id" className="w-52">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Semua region</SelectItem>
                        {regions.map((region) => (
                            <SelectItem
                                key={region.id}
                                value={String(region.id)}
                            >
                                {region.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}
