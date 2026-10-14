import { Link } from '@inertiajs/react';
import { CalendarClock, MapPin, Truck, UserRound } from 'lucide-react';
import type { ReactNode } from 'react';
import { ApprovalActions } from '@/components/approvals/approval-actions';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime, formatSchedule } from '@/lib/format';
import { show } from '@/routes/bookings';
import type { BookingApproval } from '@/types';

/**
 * Ringkasan booking dari sudut pandang satu approval di inbox approver.
 */
export function ApprovalCard({ approval }: { approval: BookingApproval }) {
    const booking = approval.booking;

    if (!booking) {
        return null;
    }

    const totalLevels = booking.approval_progress?.total ?? approval.level;

    return (
        <Card className="py-4">
            <CardContent className="grid gap-4 px-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="grid gap-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <Link
                                href={show(booking.id)}
                                className="font-semibold hover:underline"
                            >
                                {booking.code}
                            </Link>
                            <StatusBadge
                                label={booking.status_label}
                                color={booking.status_color}
                            />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Level {approval.level} dari {totalLevels}
                            {booking.status === 'pending' &&
                                approval.status === 'pending' &&
                                approval.level > booking.current_level &&
                                ` · menunggu level ${booking.current_level}`}
                        </p>
                    </div>
                    {approval.status !== 'pending' && (
                        <StatusBadge
                            label={`Keputusan saya: ${approval.status_label}`}
                            color={approval.status_color}
                        />
                    )}
                </div>

                <div className="grid gap-3 text-sm sm:grid-cols-2">
                    <Info icon={<UserRound />}>
                        {booking.requester_name}
                        <span className="text-muted-foreground">
                            {' '}
                            · {booking.requester_department}
                        </span>
                    </Info>
                    <Info icon={<CalendarClock />}>
                        {formatSchedule(booking.start_at, booking.end_at)}
                    </Info>
                    <Info icon={<Truck />}>
                        {booking.vehicle?.plate_number} ·{' '}
                        {booking.vehicle?.name}
                        <span className="text-muted-foreground">
                            {' '}
                            · {booking.driver?.name}
                        </span>
                    </Info>
                    <Info icon={<MapPin />}>{booking.destination}</Info>
                </div>

                <p className="line-clamp-2 text-sm text-muted-foreground">
                    {booking.purpose}
                </p>

                {approval.note && (
                    <p className="rounded-md bg-muted px-3 py-2 text-sm">
                        “{approval.note}”
                        {approval.acted_at && (
                            <span className="mt-1 block text-xs text-muted-foreground">
                                {formatDateTime(approval.acted_at)}
                            </span>
                        )}
                    </p>
                )}

                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={show(booking.id)}>Lihat detail</Link>
                    </Button>
                    {approval.can_process && (
                        <ApprovalActions
                            size="sm"
                            bookingCode={booking.code}
                            approval={{
                                id: approval.id,
                                level: approval.level,
                                total_levels: totalLevels,
                            }}
                        />
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

function Info({ icon, children }: { icon: ReactNode; children: ReactNode }) {
    return (
        <div className="flex items-start gap-2 [&>svg]:mt-0.5 [&>svg]:size-4 [&>svg]:shrink-0 [&>svg]:text-muted-foreground">
            {icon}
            <span className="min-w-0">{children}</span>
        </div>
    );
}
