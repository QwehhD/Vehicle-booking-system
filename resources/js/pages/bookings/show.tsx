import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { ApprovalTimeline } from '@/components/bookings/approval-timeline';
import { BookingActivity } from '@/components/bookings/booking-activity';
import { CancelBookingDialog } from '@/components/bookings/cancel-booking-dialog';
import { CompleteBookingDialog } from '@/components/bookings/complete-booking-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTime, formatNumber } from '@/lib/format';
import { index as approvalsIndex } from '@/routes/approvals';
import { index, show } from '@/routes/bookings';
import type { Auth, BookingShowProps } from '@/types';

export default function BookingsShow({
    booking,
    activityLogs,
    can,
}: BookingShowProps) {
    const vehicle = booking.vehicle;
    const driver = booking.driver;

    return (
        <>
            <Head title={booking.code} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="grid gap-1">
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-xl font-semibold tracking-tight">
                                {booking.code}
                            </h2>
                            <StatusBadge
                                label={booking.status_label}
                                color={booking.status_color}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Dibuat oleh {booking.creator?.name} pada{' '}
                            {formatDateTime(booking.created_at)}
                        </p>
                    </div>
                    {(can.cancel || can.complete) && (
                        <div className="flex flex-wrap gap-2">
                            {can.complete && (
                                <CompleteBookingDialog booking={booking} />
                            )}
                            {can.cancel && (
                                <CancelBookingDialog booking={booking} />
                            )}
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="grid content-start gap-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Detail Perjalanan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <Detail label="Pemohon">
                                        {booking.requester_name}
                                        <span className="block text-sm text-muted-foreground">
                                            {booking.requester_department}
                                        </span>
                                    </Detail>
                                    <Detail label="Tujuan">
                                        {booking.destination}
                                    </Detail>
                                    <Detail label="Mulai">
                                        {formatDateTime(booking.start_at)}
                                    </Detail>
                                    <Detail label="Selesai">
                                        {formatDateTime(booking.end_at)}
                                    </Detail>
                                    <Detail label="Keperluan" wide>
                                        <span className="whitespace-pre-line">
                                            {booking.purpose}
                                        </span>
                                    </Detail>
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Kendaraan & Driver</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    <Detail label="Kendaraan">
                                        {vehicle?.plate_number}
                                        <span className="block text-sm text-muted-foreground">
                                            {vehicle?.name}
                                        </span>
                                    </Detail>
                                    <Detail label="Jenis & kepemilikan">
                                        {vehicle?.type_label}
                                        <span className="block text-sm text-muted-foreground">
                                            {vehicle?.ownership_label}
                                            {vehicle?.rental_company &&
                                                ` · ${vehicle.rental_company}`}
                                        </span>
                                    </Detail>
                                    <Detail label="Lokasi pool">
                                        {vehicle?.location?.name}
                                        <span className="block text-sm text-muted-foreground">
                                            {vehicle?.location?.region?.name}
                                        </span>
                                    </Detail>
                                    <Detail label="Driver">
                                        {driver?.name}
                                        <span className="block text-sm text-muted-foreground">
                                            {driver?.phone}
                                        </span>
                                    </Detail>
                                </dl>
                            </CardContent>
                        </Card>

                        {booking.status === 'completed' && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Penyelesaian</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        <Detail label="Odometer">
                                            {formatNumber(
                                                booking.odometer_start,
                                            )}{' '}
                                            →{' '}
                                            {formatNumber(booking.odometer_end)}{' '}
                                            km
                                        </Detail>
                                        <Detail label="Jarak tempuh">
                                            {formatNumber(booking.distance)} km
                                        </Detail>
                                        <Detail label="BBM terpakai">
                                            {formatNumber(booking.fuel_used, 2)}{' '}
                                            liter
                                        </Detail>
                                        <Detail label="Diselesaikan">
                                            {formatDateTime(
                                                booking.completed_at,
                                            )}
                                        </Detail>
                                    </dl>
                                </CardContent>
                            </Card>
                        )}

                        {booking.status === 'cancelled' && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Pembatalan</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        <Detail label="Alasan" wide>
                                            {booking.cancel_reason}
                                        </Detail>
                                        <Detail label="Dibatalkan">
                                            {formatDateTime(
                                                booking.cancelled_at,
                                            )}
                                        </Detail>
                                    </dl>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="grid content-start gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Persetujuan
                                    {booking.approval_progress && (
                                        <span className="ml-2 text-sm font-normal text-muted-foreground">
                                            {booking.approval_progress.approved}
                                            /{booking.approval_progress.total}{' '}
                                            disetujui
                                        </span>
                                    )}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ApprovalTimeline
                                    approvals={booking.approvals ?? []}
                                    currentLevel={booking.current_level}
                                    isPending={booking.status === 'pending'}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Riwayat Aktivitas</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <BookingActivity logs={activityLogs} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

function Detail({
    label,
    wide = false,
    children,
}: {
    label: string;
    wide?: boolean;
    children: ReactNode;
}) {
    return (
        <div className={wide ? 'sm:col-span-2' : undefined}>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="mt-0.5 font-medium">{children}</dd>
        </div>
    );
}

BookingsShow.layout = (props: BookingShowProps & { auth: Auth }) => ({
    breadcrumbs: [
        props.auth.user.role === 'admin'
            ? { title: 'Pemesanan', href: index() }
            : { title: 'Persetujuan', href: approvalsIndex() },
        { title: props.booking.code, href: show(props.booking.id) },
    ],
});
