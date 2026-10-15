import { longDateParts } from '@/lib/format';
import { cn } from '@/lib/utils';

/**
 * Tanggal hari ini (WIB) dalam huruf besar bertumpuk, seperti penanda
 * tanggal pada desain referensi.
 */
export function TodayDate({ className }: { className?: string }) {
    const { day, month, year, iso } = longDateParts(new Date());

    return (
        <time
            dateTime={iso}
            className={cn(
                'grid font-display leading-[1.05] font-light tracking-tight',
                className,
            )}
        >
            <span>{day}</span>
            <span>{month}</span>
            <span>{year}</span>
        </time>
    );
}
