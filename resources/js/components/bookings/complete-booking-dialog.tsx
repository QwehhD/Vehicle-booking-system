import { useForm } from '@inertiajs/react';
import { CircleCheck } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatNumber } from '@/lib/format';
import { complete } from '@/routes/bookings';
import type { Booking } from '@/types';

export function CompleteBookingDialog({ booking }: { booking: Booking }) {
    const [open, setOpen] = useState(false);
    const form = useForm({
        odometer_start: String(booking.vehicle?.odometer ?? ''),
        odometer_end: '',
        fuel_used: '',
    });

    const start = Number(form.data.odometer_start);
    const end = Number(form.data.odometer_end);
    const distance =
        form.data.odometer_start !== '' &&
        form.data.odometer_end !== '' &&
        end >= start
            ? end - start
            : null;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.submit(complete(booking.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) {
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>
                <Button>
                    <CircleCheck />
                    Selesaikan
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>Selesaikan {booking.code}</DialogTitle>
                        <DialogDescription>
                            Catat odometer dan BBM terpakai. Odometer kendaraan{' '}
                            {booking.vehicle?.plate_number} akan diperbarui ke
                            odometer akhir.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="odometer_start">
                                Odometer awal (km)
                            </Label>
                            <Input
                                id="odometer_start"
                                type="number"
                                inputMode="numeric"
                                min={0}
                                value={form.data.odometer_start}
                                onChange={(event) =>
                                    form.setData(
                                        'odometer_start',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    form.errors.odometer_start,
                                )}
                            />
                            <InputError message={form.errors.odometer_start} />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="odometer_end">
                                Odometer akhir (km)
                            </Label>
                            <Input
                                id="odometer_end"
                                type="number"
                                inputMode="numeric"
                                min={form.data.odometer_start || 0}
                                value={form.data.odometer_end}
                                onChange={(event) =>
                                    form.setData(
                                        'odometer_end',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(form.errors.odometer_end)}
                                autoFocus
                            />
                            <InputError message={form.errors.odometer_end} />
                        </div>
                        <div className="grid gap-1.5 sm:col-span-2">
                            <Label htmlFor="fuel_used">
                                BBM terpakai (liter)
                            </Label>
                            <Input
                                id="fuel_used"
                                type="number"
                                inputMode="decimal"
                                min={0}
                                step="0.01"
                                value={form.data.fuel_used}
                                onChange={(event) =>
                                    form.setData(
                                        'fuel_used',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(form.errors.fuel_used)}
                            />
                            <InputError message={form.errors.fuel_used} />
                        </div>
                    </div>
                    {distance !== null && (
                        <p className="text-sm text-muted-foreground">
                            Jarak tempuh: {formatNumber(distance)} km
                        </p>
                    )}
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Kembali
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Simpan Penyelesaian
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
