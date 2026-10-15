import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { ChartCard } from '@/components/dashboard/chart-card';
import type { ChartConfig } from '@/components/ui/chart';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
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
import type { TopVehicle } from '@/types';

const config = {
    bookings: { label: 'Pemakaian', theme: SERIES.blue },
} satisfies ChartConfig;

function VehicleTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: { payload: TopVehicle }[];
}) {
    const vehicle = payload?.[0]?.payload;

    if (!active || !vehicle) {
        return null;
    }

    return (
        <div className="grid min-w-40 gap-1 rounded-lg border bg-background px-2.5 py-1.5 text-xs shadow-xl">
            <div className="font-medium">{vehicle.plate_number}</div>
            <div className="text-muted-foreground">{vehicle.name}</div>
            <div className="flex justify-between gap-4">
                <span className="text-muted-foreground">Pemakaian</span>
                <span className="font-mono tabular-nums">
                    {formatNumber(vehicle.bookings)} kali
                </span>
            </div>
            <div className="flex justify-between gap-4">
                <span className="text-muted-foreground">Total km</span>
                <span className="font-mono tabular-nums">
                    {formatNumber(vehicle.distance)} km
                </span>
            </div>
        </div>
    );
}

export function TopVehiclesChart({
    data,
    className,
}: {
    data: TopVehicle[];
    className?: string;
}) {
    return (
        <ChartCard
            title="Kendaraan paling sering dipakai"
            description="10 teratas dari pemesanan disetujui dan selesai."
            className={className}
            chart={
                data.length === 0 ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">
                        Belum ada pemakaian kendaraan pada filter ini.
                    </p>
                ) : (
                    <ChartContainer
                        config={config}
                        className="aspect-auto w-full"
                        style={{ height: Math.max(data.length * 36, 120) }}
                    >
                        <BarChart
                            data={data}
                            layout="vertical"
                            margin={{ left: 8, right: 16 }}
                        >
                            <CartesianGrid horizontal={false} />
                            <XAxis
                                type="number"
                                allowDecimals={false}
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                type="category"
                                dataKey="plate_number"
                                tickLine={false}
                                axisLine={false}
                                width={96}
                            />
                            <ChartTooltip
                                cursor={{ fillOpacity: 0.4 }}
                                content={<VehicleTooltip />}
                            />
                            <Bar
                                dataKey="bookings"
                                fill="var(--color-bookings)"
                                radius={[0, 4, 4, 0]}
                                maxBarSize={20}
                            />
                        </BarChart>
                    </ChartContainer>
                )
            }
            table={
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Kendaraan</TableHead>
                            <TableHead className="text-right">
                                Pemakaian
                            </TableHead>
                            <TableHead className="text-right">
                                Total km
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.map((vehicle) => (
                            <TableRow key={vehicle.id}>
                                <TableCell>
                                    <div>{vehicle.plate_number}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {vehicle.name}
                                    </div>
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatNumber(vehicle.bookings)}
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {formatNumber(vehicle.distance)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            }
        />
    );
}
