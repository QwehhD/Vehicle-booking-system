import { formatDateTime } from '@/lib/format';
import type { ActivityLog } from '@/types';

/**
 * Riwayat log aktivitas satu pemesanan, terbaru di atas.
 */
export function BookingActivity({ logs }: { logs: ActivityLog[] }) {
    if (logs.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Belum ada aktivitas.
            </p>
        );
    }

    return (
        <ul className="grid gap-4">
            {logs.map((log) => (
                <li key={log.id} className="grid gap-0.5">
                    <p className="text-sm">{log.description}</p>
                    <p className="text-xs text-muted-foreground">
                        {formatDateTime(log.created_at)}
                        {log.ip_address && ` · ${log.ip_address}`}
                    </p>
                </li>
            ))}
        </ul>
    );
}
