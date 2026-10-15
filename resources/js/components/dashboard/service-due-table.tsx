import { Link } from '@inertiajs/react';
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
import { ServiceDue } from '@/components/vehicles/service-due';
import { show } from '@/routes/vehicles';
import type { ServiceDueVehicle } from '@/types';

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
                                <TableHead>Jadwal service</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Lokasi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {vehicles.map((vehicle) => (
                                <TableRow key={vehicle.id}>
                                    <TableCell>
                                        <Link
                                            href={show(vehicle.id)}
                                            className="font-medium hover:underline"
                                        >
                                            {vehicle.plate_number}
                                        </Link>
                                        <div className="text-xs text-muted-foreground">
                                            {vehicle.name}
                                        </div>
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        <ServiceDue
                                            date={vehicle.next_service_at}
                                            daysLeft={vehicle.days_left}
                                        />
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge
                                            label={vehicle.status_label}
                                            color={vehicle.status_color}
                                        />
                                    </TableCell>
                                    <TableCell>{vehicle.location}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    );
}
