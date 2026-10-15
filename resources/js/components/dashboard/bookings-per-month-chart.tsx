import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { ChartCard } from '@/components/dashboard/chart-card';
import type { ChartConfig } from '@/components/ui/chart';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
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
import { BOOKING_STATUS_SERIES } from '@/lib/chart-palette';
import { formatNumber } from '@/lib/format';
import type { MonthlyBookings } from '@/types';

const config = Object.fromEntries(
    BOOKING_STATUS_SERIES.map((series) => [
        series.key,
        { label: series.label, theme: series.theme },
    ]),
) satisfies ChartConfig;

const lastSeries = BOOKING_STATUS_SERIES.length - 1;

function total(row: MonthlyBookings): number {
    return BOOKING_STATUS_SERIES.reduce(
        (sum, series) => sum + row[series.key],
        0,
    );
}

export function BookingsPerMonthChart({
    data,
    year,
    className,
}: {
    data: MonthlyBookings[];
    year: number;
    className?: string;
}) {
    return (
        <ChartCard
            title="Pemesanan per bulan"
            description={`Jumlah pemesanan menurut bulan mulai dan status, ${year}.`}
            className={className}
            chart={
                <ChartContainer
                    config={config}
                    className="aspect-auto h-72 w-full"
                >
                    <BarChart
                        data={data}
                        margin={{ top: 8, left: -20, right: 4 }}
                    >
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                        />
                        <YAxis
                            allowDecimals={false}
                            tickLine={false}
                            axisLine={false}
                            width={48}
                        />
                        <ChartTooltip
                            cursor={{ fillOpacity: 0.4 }}
                            content={<ChartTooltipContent />}
                        />
                        <ChartLegend content={<ChartLegendContent />} />
                        {BOOKING_STATUS_SERIES.map((series, index) => (
                            <Bar
                                key={series.key}
                                dataKey={series.key}
                                stackId="status"
                                fill={`var(--color-${series.key})`}
                                stroke="var(--card)"
                                strokeWidth={2}
                                maxBarSize={32}
                                radius={index === lastSeries ? [4, 4, 0, 0] : 0}
                            />
                        ))}
                    </BarChart>
                </ChartContainer>
            }
            table={
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Bulan</TableHead>
                            {BOOKING_STATUS_SERIES.map((series) => (
                                <TableHead
                                    key={series.key}
                                    className="text-right"
                                >
                                    {series.label}
                                </TableHead>
                            ))}
                            <TableHead className="text-right">Total</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.map((row) => (
                            <TableRow key={row.month}>
                                <TableCell>{row.label}</TableCell>
                                {BOOKING_STATUS_SERIES.map((series) => (
                                    <TableCell
                                        key={series.key}
                                        className="text-right tabular-nums"
                                    >
                                        {formatNumber(row[series.key])}
                                    </TableCell>
                                ))}
                                <TableCell className="text-right font-medium tabular-nums">
                                    {formatNumber(total(row))}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            }
        />
    );
}
