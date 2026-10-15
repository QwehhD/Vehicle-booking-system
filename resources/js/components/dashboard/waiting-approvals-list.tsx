import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatSchedule } from '@/lib/format';
import { index } from '@/routes/approvals';
import { show } from '@/routes/bookings';
import type { BookingApproval } from '@/types';

export function WaitingApprovalsList({
    approvals,
}: {
    approvals: BookingApproval[];
}) {
    return (
        <Card className="gap-4">
            <CardHeader>
                <CardTitle>Menunggu giliran Anda</CardTitle>
                <CardDescription>
                    5 pemesanan terbaru yang perlu Anda setujui atau tolak.{' '}
                    <Link
                        href={index()}
                        className="underline underline-offset-4"
                    >
                        Lihat semua
                    </Link>
                </CardDescription>
            </CardHeader>
            <CardContent>
                {approvals.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Tidak ada pemesanan yang menunggu persetujuan Anda.
                    </p>
                ) : (
                    <ul className="divide-y">
                        {approvals.map((approval) =>
                            approval.booking ? (
                                <li key={approval.id}>
                                    <Link
                                        href={show(approval.booking.id)}
                                        className="flex items-center gap-3 py-3 hover:bg-muted/50"
                                    >
                                        <div className="grid min-w-0 flex-1 gap-0.5">
                                            <div className="flex flex-wrap items-center gap-x-2 text-sm">
                                                <span className="font-medium">
                                                    {approval.booking.code}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    Level {approval.level} dari{' '}
                                                    {approval.booking
                                                        .approval_progress
                                                        ?.total ??
                                                        approval.level}
                                                </span>
                                            </div>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {
                                                    approval.booking
                                                        .requester_name
                                                }{' '}
                                                ·{' '}
                                                {
                                                    approval.booking.vehicle
                                                        ?.plate_number
                                                }{' '}
                                                ·{' '}
                                                {formatSchedule(
                                                    approval.booking.start_at,
                                                    approval.booking.end_at,
                                                )}
                                            </p>
                                        </div>
                                        <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                    </Link>
                                </li>
                            ) : null,
                        )}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
