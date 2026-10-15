import { Head, Link } from '@inertiajs/react';
import { Fuel, Gauge, Route, Timer } from 'lucide-react';
import type { ReactNode } from 'react';
import { LoadingRegion } from '@/components/loading-region';
import { Pagination } from '@/components/pagination';
import { StatCard, StatGrid } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ServiceDue } from '@/components/vehicles/service-due';
import {
    formatDate,
    formatKm,
    formatLiters,
    formatNumber,
    formatSchedule,
} from '@/lib/format';
import { show as bookingShow } from '@/routes/bookings';
import { index, show } from '@/routes/vehicles';
import type { VehicleShowProps } from '@/types';

export default function VehiclesShow({
    vehicle,
    summary,
    bookings,
}: VehicleShowProps) {
    return (
        <>
            <Head title={vehicle.plate_number} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="grid gap-1">
                    <div className="flex flex-wrap items-center gap-3">
                        <h2 className="text-xl font-semibold tracking-tight">
                            {vehicle.plate_number}
                        </h2>
                        <StatusBadge
                            label={vehicle.status_label}
                            color={vehicle.status_color}
                        />
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {vehicle.name}
                    </p>
                </div>

                <StatGrid className="reveal grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Perjalanan selesai"
                        value={summary.trips}
                        icon={Route}
                    />
                    <StatCard
                        label="Total jarak"
                        value={summary.distance}
                        format={formatKm}
                        icon={Gauge}
                    />
                    <StatCard
                        label="Total BBM"
                        value={summary.fuel}
                        format={formatLiters}
                        icon={Fuel}
                    />
                    <StatCard
                        label="Rata-rata konsumsi"
                        value={
                            summary.km_per_liter === null
                                ? '–'
                                : `${formatNumber(summary.km_per_liter, 1)} km/L`
                        }
                        icon={Timer}
                    />
                </StatGrid>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Informasi kendaraan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <Detail label="Jenis">
                                    {vehicle.type_label}
                                </Detail>
                                <Detail label="Kepemilikan">
                                    {vehicle.ownership_label}
                                    {vehicle.rental_company && (
                                        <span className="block text-sm text-muted-foreground">
                                            {vehicle.rental_company}
                                        </span>
                                    )}
                                </Detail>
                                <Detail label="Lokasi pool">
                                    {vehicle.location?.name}
                                    <span className="block text-sm text-muted-foreground">
                                        {vehicle.location?.region?.name}
                                    </span>
                                </Detail>
                                <Detail label="Odometer">
                                    {formatNumber(vehicle.odometer)} km
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Jadwal service</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4">
                                <Detail label="Service terakhir">
                                    {formatDate(vehicle.last_service_at)}
                                </Detail>
                                <Detail label="Service berikutnya">
                                    <ServiceDue
                                        date={vehicle.next_service_at}
                                        daysLeft={vehicle.days_until_service}
                                    />
                                </Detail>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card className="gap-4">
                    <CardHeader>
                        <CardTitle>Riwayat pemakaian</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4">
                        <LoadingRegion className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Jadwal</TableHead>
                                        <TableHead>Pemohon</TableHead>
                                        <TableHead>Driver</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Km
                                        </TableHead>
                                        <TableHead className="text-right">
                                            BBM (L)
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bookings.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="h-24 text-center text-muted-foreground"
                                            >
                                                Kendaraan ini belum pernah
                                                dipesan.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        bookings.data.map((booking) => (
                                            <TableRow key={booking.id}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        href={bookingShow(
                                                            booking.id,
                                                        )}
                                                        className="hover:underline"
                                                    >
                                                        {booking.code}
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatSchedule(
                                                        booking.start_at,
                                                        booking.end_at,
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {booking.requester_name}
                                                </TableCell>
                                                <TableCell>
                                                    {booking.driver?.name}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        label={
                                                            booking.status_label
                                                        }
                                                        color={
                                                            booking.status_color
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {booking.distance === null
                                                        ? '–'
                                                        : formatNumber(
                                                              booking.distance,
                                                          )}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {booking.fuel_used === null
                                                        ? '–'
                                                        : formatNumber(
                                                              booking.fuel_used,
                                                              2,
                                                          )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </LoadingRegion>
                        <Pagination
                            meta={bookings.meta}
                            links={bookings.links}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 font-medium">{children}</dd>
        </div>
    );
}

VehiclesShow.layout = (props: VehicleShowProps) => ({
    breadcrumbs: [
        { title: 'Kendaraan', href: index() },
        { title: props.vehicle.plate_number, href: show(props.vehicle.id) },
    ],
});
