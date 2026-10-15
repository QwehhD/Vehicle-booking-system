import { StatusBadge } from '@/components/status-badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ServiceDueVehicle } from '@/types';

function dueLabel(daysLeft: number): string {
    if (daysLeft < 0) {
        return `Terlambat ${Math.abs(daysLeft)} hari`;
    }

    return daysLeft === 0 ? 'Hari ini' : `${daysLeft} hari lagi`;
}

export function ServiceDueTable({
    vehicles,
}: {
    vehicles: ServiceDueVehicle[];
}) {
    return (
        <Card className="gap-4">
            <CardHeader>
                <CardTitle>Perlu service segera</CardTitle>
                <CardDescription>
                    Kendaraan dengan jadwal service paling lambat 14 hari lagi.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {vehicles.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Tidak ada kendaraan yang perlu service dalam 14 hari.
                    </p>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Kendaraan</TableHead>
                                <TableHead>Lokasi</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Jadwal service</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.map((vehicle) => (
                                <TableRow key={vehicle.id}>
                                    <TableCell>
                                        <div className="font-medium">
                                            {vehicle.plate_number}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {vehicle.name}
                                        </div>
                                    </TableCell>
                                    <TableCell>{vehicle.location}</TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            label={vehicle.status_label}
                                            color={vehicle.status_color}
                                        />
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        <div>
                                            {formatDate(
                                                vehicle.next_service_at,
                                            )}
                                        </div>
                                        <div
                                            className={cn(
                                                'text-xs',
                                                vehicle.days_left < 0
                                                    ? 'font-medium text-red-600 dark:text-red-400'
                                                    : 'text-muted-foreground',
                                            )}
                                        >
                                            {dueLabel(vehicle.days_left)}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}
