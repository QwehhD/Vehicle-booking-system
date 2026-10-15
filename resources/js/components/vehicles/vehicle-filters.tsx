import { router } from '@inertiajs/react';
import { X } from 'lucide-react';
import { FilterSelect } from '@/components/filter-select';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/vehicles';
import type { Option, VehicleFilters as Filters } from '@/types';

export function VehicleFilters({
    filters,
    types,
    ownerships,
    statuses,
    locations,
}: {
    filters: Filters;
    types: Option[];
    ownerships: Option[];
    statuses: Option[];
    locations: { id: number; name: string }[];
}) {
    const apply = (changes: Partial<Filters>) => {
        const next = { ...filters, ...changes };

        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(next).filter(([, value]) => value !== null),
            ) as Record<string, string | number>,
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilters = Object.values(filters).some((value) => value !== null);

    return (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <FilterSelect
                id="type"
                label="Jenis"
                value={filters.type}
                options={types}
                onChange={(type) => apply({ type: type as Filters['type'] })}
            />
            <FilterSelect
                id="ownership"
                label="Kepemilikan"
                value={filters.ownership}
                options={ownerships}
                onChange={(ownership) =>
                    apply({ ownership: ownership as Filters['ownership'] })
                }
            />
            <FilterSelect
                id="location_id"
                label="Lokasi pool"
                value={filters.location_id?.toString() ?? null}
                options={locations.map((location) => ({
                    value: location.id.toString(),
                    label: location.name,
                }))}
                onChange={(location) =>
                    apply({ location_id: location ? Number(location) : null })
                }
            />
            <FilterSelect
                id="status"
                label="Status"
                value={filters.status}
                options={statuses}
                onChange={(status) =>
                    apply({ status: status as Filters['status'] })
                }
            />
            {hasFilters && (
                <div className="col-span-2 lg:col-span-4">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            router.get(index.url(), {}, { replace: true })
                        }
                    >
                        <X />
                        Hapus filter
                    </Button>
                </div>
            )}
        </div>
    );
}
