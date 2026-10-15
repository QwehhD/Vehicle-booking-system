import { CartesianGrid, Line, LineChart, XAxis, YAxis } from 'recharts';
import { ChartCard } from '@/components/dashboard/chart-card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { SERIES } from '@/lib/chart-palette';
import { formatNumber } from '@/lib/format';
import type { MonthlyUsage } from '@/types';

const config = {
    distance: { label: 'Jarak tempuh (km)', theme: SERIES.blue },
    fuel: { label: 'BBM (liter)', theme: SERIES.orange },
} satisfies ChartConfig;

/**
 * Satu panel garis per besaran: km dan liter tidak berbagi sumbu-y.
 */
function Panel({
    data,
    dataKey,
    title,
}: {
    data: MonthlyUsage[];
    dataKey: 'distance' | 'fuel';
    title: string;
}) {
    return (
        <div className="grid gap-2">
            <p className="text-sm font-medium">{title}</p>
            <ChartContainer config={config} className="aspect-auto h-40 w-full">
                <LineChart data={data} margin={{ top: 8, left: -12, right: 8 }}>
                    <CartesianGrid vertical={false} />
                    <XAxis
                        dataKey="label"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={8}
                    />
                    <YAxis
                        tickLine={false}
                        axisLine={false}
                        width={52}
                        tickFormatter={(value: number) => formatNumber(value)}
                    />
                    <ChartTooltip
                        content={<ChartTooltipContent indicator="line" />}
                    />
                    <Line
                        dataKey={dataKey}
                        type="monotone"
                        stroke={`var(--color-${dataKey})`}
                        strokeWidth={2}
                        dot={false}
                        activeDot={{
                            r: 4,
                            strokeWidth: 2,
                            stroke: 'var(--card)',
                        }}
                    />
                </LineChart>
            </ChartContainer>
        </div>
    );
}

export function DistanceFuelChart({
    data,
    className,
}: {
    data: MonthlyUsage[];
    className?: string;
}) {
    return (
        <ChartCard
            title="Jarak tempuh & konsumsi BBM"
            description="Per bulan dari pemesanan yang sudah selesai."
            className={className}
            chart={
                <div className="grid gap-4">
                    <Panel
                        data={data}
                        dataKey="distance"
                        title="Jarak tempuh (km)"
                    />
                    <Panel
                        data={data}
                        dataKey="fuel"
                        title="Konsumsi BBM (liter)"
                    />
                </div>
            }
            table={
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bulan</TableHead>
                            <TableHead className="text-right">
                                Jarak (km)
                            </TableHead>
                            <TableHead className="text-right">
                                BBM (L)
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.map((row) => (
                            <TableRow key={row.month}>
                                <TableCell>{row.label}</TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatNumber(row.distance)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatNumber(row.fuel, 2)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            }
        />
    );
}
