import { Head, Link } from '@inertiajs/react';
import {
    Ban,
    CalendarCheck,
    CircleCheck,
    Download,
    Fuel,
    Gauge,
    XCircle,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Pagination } from '@/components/pagination';
import { ReportFilters } from '@/components/reports/report-filters';
import { StatCard } from '@/components/stat-card';
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
import { formatDate, formatNumber, formatSchedule } from '@/lib/format';
import { show } from '@/routes/bookings';
import { exportMethod, index } from '@/routes/reports';
import type { ReportFilters as Filters, ReportIndexProps } from '@/types';

function exportQuery(filters: Filters): Record<string, string | number> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== null),
    ) as Record<string, string | number>;
}

export default function ReportsIndex({
    filters,
    summary,
    bookings,
    statuses,
    vehicleTypes,
    regions,
}: ReportIndexProps) {
    const period = `${formatDate(filters.date_from)} – ${formatDate(filters.date_to)}`;

    return (
        <>
            <Head title="Laporan" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Laporan"
                        description="Laporan periodik pemesanan kendaraan berdasarkan jadwal mulai."
                    />
                    <Button asChild>
                        <a
                            href={exportMethod.url({
                                query: exportQuery(filters),
                            })}
                        >
                            <Download />
                            Export Excel
                        </a>
                    </Button>
                </div>

                <ReportFilters
                    filters={filters}
                    statuses={statuses}
                    vehicleTypes={vehicleTypes}
                    regions={regions}
                />

                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
                    <StatCard
                        label="Jumlah pemesanan"
                        value={formatNumber(summary.total)}
                        hint={period}
                        icon={CalendarCheck}
                    />
                    <StatCard
                        label="Disetujui + selesai"
                        value={formatNumber(summary.approved_or_completed)}
                        icon={CircleCheck}
                    />
                    <StatCard
                        label="Ditolak"
                        value={formatNumber(summary.rejected)}
                        icon={XCircle}
                    />
                    <StatCard
                        label="Dibatalkan"
                        value={formatNumber(summary.cancelled)}
                        icon={Ban}
                    />
                    <StatCard
                        label="Total km"
                        value={formatNumber(summary.distance)}
                        hint="Pemesanan selesai"
                        icon={Gauge}
                    />
                    <StatCard
                        label="Total BBM"
                        value={`${formatNumber(summary.fuel, 2)} L`}
                        hint="Pemesanan selesai"
                        icon={Fuel}
                    />
                </div>

                <div className="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kode</TableHead>
                                <TableHead>Jadwal</TableHead>
                                <TableHead>Pemohon</TableHead>
                                <TableHead>Kendaraan</TableHead>
                                <TableHead>Region</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-center">
                                    Persetujuan
                                </TableHead>
                                <TableHead className="text-right">Km</TableHead>
                                <TableHead className="text-right">
                                    BBM (L)
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {bookings.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        Tidak ada pemesanan pada periode dan
                                        filter ini.
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
                                        <TableCell className="whitespace-nowrap">
                                            {formatSchedule(
                                                booking.start_at,
                                                booking.end_at,
                                            )}
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
                                                {booking.vehicle?.type_label}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {
                                                booking.vehicle?.location
                                                    ?.region?.name
                                            }
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
                </div>

                <Pagination meta={bookings.meta} links={bookings.links} />
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Laporan',
            href: index(),
        },
    ],
};
