import { BarChart3, Table2 } from 'lucide-react';
import type { ReactNode } from 'react';
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
}: Props) {
    const [view, setView] = useState<'chart' | 'table'>('chart');

    return (
        <Card className={cn('gap-4', className)}>
            <CardHeader className="flex flex-row items-start justify-between gap-4">
                <div className="grid gap-1.5">
                    <CardTitle>{title}</CardTitle>
                    {description && (
                        <CardDescription>{description}</CardDescription>
                    )}
                </div>
                <div
                    className="flex shrink-0 rounded-md border p-0.5"
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
            <CardContent>{view === 'chart' ? chart : table}</CardContent>
        </Card>
    );
}
