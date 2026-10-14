import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/bookings';
import type { BookingFilters as Filters, Option } from '@/types';

const ALL = 'all';

type Props = {
    filters: Filters;
    statuses: Option[];
    vehicleTypes: Option[];
    regions: { id: number; name: string }[];
};

function withoutEmpty(filters: Filters): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== null && value !== '',
        ),
    ) as Record<string, string | number>;
}

export function BookingFilters({
    filters,
    statuses,
    vehicleTypes,
    regions,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const searchTimeout = useRef<ReturnType<typeof setTimeout>>(undefined);

    const apply = (changes: Partial<Filters>) => {
        router.get(index.url(), withoutEmpty({ ...filters, ...changes }), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const changeSearch = (value: string) => {
        setSearch(value);
        clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(
            () => apply({ search: value.trim() || null }),
            400,
        );
    };

    useEffect(() => () => clearTimeout(searchTimeout.current), []);

    const hasFilters = Object.values(filters).some(
        (value) => value !== null && value !== '',
    );

    return (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div className="grid gap-1.5 sm:col-span-2">
                <Label htmlFor="search">Cari</Label>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="search"
                        value={search}
                        onChange={(event) => changeSearch(event.target.value)}
                        placeholder="Kode, pemohon, atau plat"
                        className="pl-9"
                    />
                </div>
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

            <div className="grid gap-1.5">
                <Label htmlFor="date_from">Mulai dari</Label>
                <Input
                    id="date_from"
                    type="date"
                    value={filters.date_from ?? ''}
                    max={filters.date_to ?? undefined}
                    onChange={(event) =>
                        apply({ date_from: event.target.value || null })
                    }
                />
            </div>

            <div className="grid gap-1.5">
                <Label htmlFor="date_to">Sampai</Label>
                <Input
                    id="date_to"
                    type="date"
                    value={filters.date_to ?? ''}
                    min={filters.date_from ?? undefined}
                    onChange={(event) =>
                        apply({ date_to: event.target.value || null })
                    }
                />
            </div>

            {hasFilters && (
                <div className="flex items-end sm:col-span-2 lg:col-span-6">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            clearTimeout(searchTimeout.current);
                            setSearch('');
                            router.get(index.url(), {}, { replace: true });
                        }}
                    >
                        <X />
                        Hapus filter
                    </Button>
                </div>
            )}
        </div>
    );
}

function FilterSelect({
    id,
    label,
    value,
    options,
    onChange,
}: {
    id: string;
    label: string;
    value: string | null;
    options: Option[];
    onChange: (value: string | null) => void;
}) {
    return (
        <div className="grid gap-1.5">
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value ?? ALL}
                onValueChange={(selected) =>
                    onChange(selected === ALL ? null : selected)
                }
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>Semua</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
