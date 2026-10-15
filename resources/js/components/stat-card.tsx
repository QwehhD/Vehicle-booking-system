import type { LucideIcon } from 'lucide-react';
import type { CSSProperties, ReactNode } from 'react';
import { AnimatedNumber } from '@/components/animated-number';
import { cn } from '@/lib/utils';

/**
 * Wadah kartu angka: sel-sel dipisah garis 1px seperti tabel editorial.
 */
export function StatGrid({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            className={cn(
                'grid gap-px overflow-hidden rounded-lg border bg-border',
                className,
            )}
        >
            {children}
        </div>
    );
}

/**
 * Sel angka ringkasan di dalam `StatGrid`. Angka bertipe number dihitung
 * naik saat muncul atau berubah.
 */
export function StatCard({
    label,
    value,
    format,
    hint,
    icon: Icon,
    index = 0,
}: {
    label: string;
    value: number | string;
    format?: (value: number) => string;
    hint?: string;
    icon: LucideIcon;
    index?: number;
}) {
    return (
        <div
            style={{ '--stagger': index } as CSSProperties}
            className="@container flex min-h-32 flex-col justify-between gap-6 bg-card p-5"
        >
            <div className="reveal flex items-start justify-between gap-3">
                <div className="grid gap-1">
                    <p className="eyebrow">{label}</p>
                    {hint && (
                        <p className="text-xs text-muted-foreground/80">
                            {hint}
                        </p>
                    )}
                </div>
                <Icon className="size-4 shrink-0 text-muted-foreground" />
            </div>
            <p className="reveal font-display text-3xl font-light tracking-tight whitespace-nowrap tabular-nums @[9rem]:text-4xl">
                {typeof value === 'number' ? (
                    <AnimatedNumber value={value} format={format} />
                ) : (
                    value
                )}
            </p>
        </div>
    );
}
