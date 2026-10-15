import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { LoadingRegion } from '@/components/loading-region';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ServiceDue } from '@/components/vehicles/service-due';
import { VehicleFilters } from '@/components/vehicles/vehicle-filters';
import { formatNumber } from '@/lib/format';
import { index, show } from '@/routes/vehicles';
import type { VehicleIndexProps } from '@/types';

export default function VehiclesIndex({
    vehicles,
    filters,
    types,
    ownerships,
    statuses,
    locations,
}: VehicleIndexProps) {
    const hasFilters = Object.values(filters).some((value) => value !== null);

    return (
        <>
            <Head title="Kendaraan" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Kendaraan"
                    description="Daftar kendaraan, jadwal service, dan riwayat pemakaian."
                />

                <VehicleFilters
                    filters={filters}
                    types={types}
                    ownerships={ownerships}
                    statuses={statuses}
                    locations={locations}
                />

                <LoadingRegion className="reveal overflow-hidden rounded-lg border bg-card [--stagger:2]">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kendaraan</TableHead>
                                <TableHead>Jenis</TableHead>
                                <TableHead>Kepemilikan</TableHead>
                                <TableHead>Lokasi pool</TableHead>
                                <TableHead className="text-right">
                                    Odometer
                                </TableHead>
                                <TableHead>Service berikutnya</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Pemakaian
                                </TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        {hasFilters
                                            ? 'Tidak ada kendaraan yang cocok dengan filter.'
                                            : 'Belum ada kendaraan.'}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                vehicles.data.map((vehicle) => (
                                    <TableRow key={vehicle.id}>
                                        <TableCell>
                                            <Link
                                                href={show(vehicle.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {vehicle.plate_number}
                                            </Link>
                                            <div className="text-xs text-muted-foreground">
                                                {vehicle.name}
                                            </div>
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {vehicle.type_label}
                                        </TableCell>
                                        <TableCell>
                                            <div>{vehicle.ownership_label}</div>
                                            {vehicle.rental_company && (
                                                <div className="text-xs text-muted-foreground">
                                                    {vehicle.rental_company}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div>{vehicle.location?.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {vehicle.location?.region?.name}
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatNumber(vehicle.odometer)} km
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            <ServiceDue
                                                date={vehicle.next_service_at}
                                                daysLeft={
                                                    vehicle.days_until_service
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={vehicle.status_label}
                                                color={vehicle.status_color}
                                            />
                                        </TableCell>
                                        <TableCell className="text-right tabular-nums">
                                            {formatNumber(
                                                vehicle.completed_bookings_count ??
                                                    0,
                                            )}
                                            x
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link href={show(vehicle.id)}>
                                                    Detail
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </LoadingRegion>

                <Pagination meta={vehicles.meta} links={vehicles.links} />
            </div>
        </>
    );
}

VehiclesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Kendaraan',
            href: index(),
        },
    ],
};
