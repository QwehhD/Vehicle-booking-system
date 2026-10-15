import { BarChart3, Table2 } from 'lucide-react';
import type { CSSProperties, ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    chart: ReactNode;
    table: ReactNode;
    className?: string;
    index?: number;
};

/**
 * Kartu grafik dengan tampilan tabel sebagai alternatif yang aksesibel.
 */
export function ChartCard({
    title,
    description,
    chart,
    table,
    className,
    index = 0,
}: Props) {
    const [view, setView] = useState<'chart' | 'table'>('chart');

    return (
        <Card
            className={cn('reveal gap-4', className)}
            style={{ '--stagger': index } as CSSProperties}
        >
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div className="grid gap-1.5">
                    <CardTitle>{title}</CardTitle>
                    {description && (
                        <CardDescription>{description}</CardDescription>
                    )}
                </div>
                <div
                    className="flex shrink-0 rounded-sm border p-0.5"
                    role="group"
                    aria-label="Tampilan"
                >
                    <Button
                        type="button"
                        size="icon"
                        variant={view === 'chart' ? 'secondary' : 'ghost'}
                        className="size-7"
                        onClick={() => setView('chart')}
                        aria-pressed={view === 'chart'}
                        aria-label="Tampilkan grafik"
                    >
                        <BarChart3 />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant={view === 'table' ? 'secondary' : 'ghost'}
                        className="size-7"
                        onClick={() => setView('table')}
                        aria-pressed={view === 'table'}
                        aria-label="Tampilkan tabel"
                    >
                        <Table2 />
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div
                    key={view}
                    className="animate-in duration-500 ease-[var(--ease-out-expo)] fade-in-0 slide-in-from-bottom-1"
                >
                    {view === 'chart' ? chart : table}
                </div>
            </CardContent>
        </Card>
    );
}
