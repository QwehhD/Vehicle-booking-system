import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { BookingFilters } from '@/components/bookings/booking-filters';
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
import { formatSchedule } from '@/lib/format';
import { create, index, show } from '@/routes/bookings';
import type { BookingIndexProps } from '@/types';

export default function BookingsIndex({
    bookings,
    filters,
    statuses,
    vehicleTypes,
    regions,
}: BookingIndexProps) {
    const hasFilters = Object.values(filters).some(
        (value) => value !== null && value !== '',
    );

    return (
        <>
            <Head title="Manajemen Pemesanan" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Manajemen Pemesanan"
                        description="Daftar pemesanan kendaraan beserta status dan progres persetujuannya."
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus />
                            Buat Pemesanan
                        </Link>
                    </Button>
                </div>

                <BookingFilters
                    filters={filters}
                    statuses={statuses}
                    vehicleTypes={vehicleTypes}
                    regions={regions}
                />

                <LoadingRegion className="reveal overflow-hidden rounded-lg border bg-card [--stagger:2]">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kode</TableHead>
                                <TableHead>Pemohon</TableHead>
                                <TableHead>Kendaraan</TableHead>
                                <TableHead>Driver</TableHead>
                                <TableHead>Jadwal</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-center">
                                    Persetujuan
                                </TableHead>
                                <TableHead className="text-right">
                                    Aksi
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {bookings.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        {hasFilters
                                            ? 'Tidak ada pemesanan yang cocok dengan filter.'
                                            : 'Belum ada pemesanan.'}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                bookings.data.map((booking) => (
                                    <TableRow key={booking.id}>
                                        <TableCell className="font-medium">
                                            <Link
                                                href={show(booking.id)}
                                                className="hover:underline"
                                            >
                                                {booking.code}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            <div>{booking.requester_name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {booking.requester_department}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                {booking.vehicle?.plate_number}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {booking.vehicle?.name}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {booking.driver?.name}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {formatSchedule(
                                                booking.start_at,
                                                booking.end_at,
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={booking.status_label}
                                                color={booking.status_color}
                                            />
                                        </TableCell>
                                        <TableCell className="text-center tabular-nums">
                                            {booking.approval_progress &&
                                                `${booking.approval_progress.approved}/${booking.approval_progress.total}`}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link href={show(booking.id)}>
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

                <Pagination meta={bookings.meta} links={bookings.links} />
            </div>
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pemesanan',
            href: index(),
        },
    ],
};
