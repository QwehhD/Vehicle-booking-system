import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { StatusBadge } from '@/components/status-badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDateTime } from '@/lib/format';
import { show } from '@/routes/bookings';
import type { ActivityLog } from '@/types';

export function ActivityLogDialog({
    log,
    onClose,
}: {
    log: ActivityLog | null;
    onClose: () => void;
}) {
    return (
        <Dialog open={log !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-xl">
                {log && (
                    <>
                        <DialogHeader>
                            <DialogTitle className="flex flex-wrap items-center gap-2">
                                Detail aktivitas
                                <StatusBadge
                                    label={log.action_label}
                                    color={log.action_color}
                                />
                            </DialogTitle>
                            <DialogDescription>
                                {log.description}
                            </DialogDescription>
                        </DialogHeader>
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <Item label="Waktu">
                                {formatDateTime(log.created_at)}
                            </Item>
                            <Item label="User">{log.user?.name ?? 'Tamu'}</Item>
                            <Item label="Aksi">
                                <code className="text-xs">{log.action}</code>
                            </Item>
                            <Item label="Alamat IP">
                                {log.ip_address ?? '–'}
                            </Item>
                            {log.booking && (
                                <Item label="Pemesanan">
                                    <Link
                                        href={show(log.booking.id)}
                                        className="underline underline-offset-4"
                                    >
                                        {log.booking.code}
                                    </Link>
                                </Item>
                            )}
                            <Item label="User agent" wide>
                                <span className="break-all">
                                    {log.user_agent ?? '–'}
                                </span>
                            </Item>
                            <Item label="Properties" wide>
                                {log.properties ? (
                                    <pre className="max-h-64 overflow-auto rounded-md bg-muted p-3 text-xs">
                                        {JSON.stringify(
                                            log.properties,
                                            null,
                                            2,
                                        )}
                                    </pre>
                                ) : (
                                    '–'
                                )}
                            </Item>
                        </dl>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

function Item({
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
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-0.5">{children}</dd>
        </div>
    );
}
