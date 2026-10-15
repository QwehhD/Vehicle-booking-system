import { formatDate, formatDaysUntil } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * Tanggal service berikutnya beserta sisa harinya; ≤ 14 hari ditandai.
 */
export function ServiceDue({
    date,
    daysLeft,
}: {
    date: string | null;
    daysLeft: number | null;
}) {
    if (date === null || daysLeft === null) {
        return <span className="text-muted-foreground">Belum dijadwalkan</span>;
    }

    return (
        <span className="grid">
            <span>{formatDate(date)}</span>
            <span
                className={cn(
                    'text-xs',
                    daysLeft < 0
                        ? 'font-medium text-red-600 dark:text-red-400'
                        : daysLeft <= 14
                          ? 'font-medium text-amber-700 dark:text-amber-400'
                          : 'text-muted-foreground',
                )}
            >
                {formatDaysUntil(daysLeft)}
            </span>
        </span>
    );
}
