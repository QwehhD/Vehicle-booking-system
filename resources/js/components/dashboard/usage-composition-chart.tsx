import { Cell, Label, Pie, PieChart } from 'recharts';
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
import type { CompositionSlice } from '@/types';

const SLICE_THEMES = [SERIES.blue, SERIES.orange];

function percent(value: number, total: number): string {
    return total === 0 ? '0%' : `${Math.round((value / total) * 100)}%`;
}

function Donut({
    title,
    slices,
}: {
    title: string;
    slices: CompositionSlice[];
}) {
    const config = Object.fromEntries(
        slices.map((slice, index) => [
            slice.key,
            { label: slice.label, theme: SLICE_THEMES[index] },
        ]),
    ) satisfies ChartConfig;
    const total = slices.reduce((sum, slice) => sum + slice.value, 0);

    return (
        <div className="grid gap-3">
            <p className="text-center text-sm font-medium">{title}</p>
            <ChartContainer
                config={config}
                className="mx-auto aspect-square h-44"
            >
                <PieChart>
                    <ChartTooltip
                        content={
                            <ChartTooltipContent nameKey="key" hideLabel />
                        }
                    />
                    <Pie
                        data={slices}
                        dataKey="value"
                        nameKey="key"
                        innerRadius={52}
                        outerRadius={72}
                        stroke="var(--card)"
                        strokeWidth={2}
                    >
                        {slices.map((slice) => (
                            <Cell
                                key={slice.key}
                                fill={`var(--color-${slice.key})`}
                            />
                        ))}
                        <Label
                            content={({ viewBox }) =>
                                viewBox && 'cx' in viewBox ? (
                                    <text
                                        x={viewBox.cx}
                                        y={viewBox.cy}
                                        textAnchor="middle"
                                        dominantBaseline="middle"
                                    >
                                        <tspan
                                            x={viewBox.cx}
                                            y={viewBox.cy}
                                            className="fill-foreground text-xl font-semibold"
                                        >
                                            {formatNumber(total)}
                                        </tspan>
                                        <tspan
                                            x={viewBox.cx}
                                            y={(viewBox.cy ?? 0) + 18}
                                            className="fill-muted-foreground text-xs"
                                        >
                                            pemakaian
                                        </tspan>
                                    </text>
                                ) : null
                            }
                        />
                    </Pie>
                </PieChart>
            </ChartContainer>
            <ul className="grid gap-1.5 text-sm">
                {slices.map((slice) => (
                    <li key={slice.key} className="flex items-center gap-2">
                        <span
                            aria-hidden
                            className="size-2.5 shrink-0 rounded-[2px]"
                            style={{ background: `var(--color-${slice.key})` }}
                        />
                        <span className="flex-1 text-muted-foreground">
                            {slice.label}
                        </span>
                        <span className="tabular-nums">
                            {formatNumber(slice.value)}
                        </span>
                        <span className="w-10 text-right text-muted-foreground tabular-nums">
                            {percent(slice.value, total)}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function UsageCompositionChart({
    type,
    ownership,
    className,
}: {
    type: CompositionSlice[];
    ownership: CompositionSlice[];
    className?: string;
}) {
    return (
        <ChartCard
            title="Komposisi pemakaian"
            description="Pemesanan disetujui dan selesai."
            className={className}
            chart={
                <div className="grid gap-6 sm:grid-cols-2">
                    <Donut title="Jenis kendaraan" slices={type} />
                    <Donut title="Kepemilikan" slices={ownership} />
                </div>
            }
            table={
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kategori</TableHead>
                            <TableHead className="text-right">
                                Pemakaian
                            </TableHead>
                            <TableHead className="text-right">Porsi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {[type, ownership].flatMap((slices) => {
                            const total = slices.reduce(
                                (sum, slice) => sum + slice.value,
                                0,
                            );

                            return slices.map((slice) => (
                                <TableRow key={slice.key}>
                                    <TableCell>{slice.label}</TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatNumber(slice.value)}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {percent(slice.value, total)}
                                    </TableCell>
                                </TableRow>
                            ));
                        })}
                    </TableBody>
                </Table>
            }
        />
    );
}
