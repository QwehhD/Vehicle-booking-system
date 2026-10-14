import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { BadgeColor } from '@/types';

const COLOR_CLASSES: Record<BadgeColor, string> = {
    amber: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/15 dark:text-amber-300',
    blue: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300',
    red: 'border-red-200 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/15 dark:text-red-300',
    slate: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-500/30 dark:bg-slate-500/15 dark:text-slate-300',
    emerald:
        'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-emerald-300',
    violet: 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-300',
    orange: 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-500/30 dark:bg-orange-500/15 dark:text-orange-300',
};

/**
 * Badge berwarna sesuai `color()` enum di backend.
 */
export function StatusBadge({
    label,
    color,
    className,
}: {
    label: string;
    color: BadgeColor;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(COLOR_CLASSES[color], className)}
        >
            {label}
        </Badge>
    );
}
