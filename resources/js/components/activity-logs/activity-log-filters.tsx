import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { FilterSelect } from '@/components/filter-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/activity-logs';
import type { ActivityLogFilters as Filters, Option } from '@/types';

function withoutEmpty(filters: Filters): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(filters).filter(
            ([, value]) => value !== null && value !== '',
        ),
    ) as Record<string, string | number>;
}

export function ActivityLogFilters({
    filters,
    users,
    actions,
}: {
    filters: Filters;
    users: { id: number; name: string }[];
    actions: Option[];
}) {
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
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-6">
            <div className="col-span-2 grid gap-1.5">
                <Label htmlFor="search">Cari</Label>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="search"
                        value={search}
                        onChange={(event) => changeSearch(event.target.value)}
                        placeholder="Deskripsi atau IP"
                        className="pl-9"
                    />
                </div>
            </div>
            <FilterSelect
                id="user_id"
                label="User"
                value={filters.user_id?.toString() ?? null}
                options={users.map((user) => ({
                    value: user.id.toString(),
                    label: user.name,
                }))}
                onChange={(user) =>
                    apply({ user_id: user ? Number(user) : null })
                }
            />
            <FilterSelect
                id="action"
                label="Aksi"
                value={filters.action}
                options={actions}
                onChange={(action) => apply({ action })}
            />
            <div className="grid gap-1.5">
                <Label htmlFor="date_from">Dari tanggal</Label>
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
                <Label htmlFor="date_to">Sampai tanggal</Label>
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
                <div className="col-span-2 lg:col-span-6">
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
