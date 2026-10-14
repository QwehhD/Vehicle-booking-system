import { useForm } from '@inertiajs/react';
import { Ban } from 'lucide-react';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cancel } from '@/routes/bookings';
import type { Booking } from '@/types';

export function CancelBookingDialog({ booking }: { booking: Booking }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ cancel_reason: '' });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.submit(cancel(booking.id), {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
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
                <Button variant="destructive">
                    <Ban />
                    Batalkan
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>Batalkan {booking.code}?</DialogTitle>
                        <DialogDescription>
                            Approval yang masih menunggu akan dilewati. Tindakan
                            ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-1.5">
                        <Label htmlFor="cancel_reason">Alasan pembatalan</Label>
                        <Textarea
                            id="cancel_reason"
                            rows={3}
                            value={form.data.cancel_reason}
                            onChange={(event) =>
                                form.setData(
                                    'cancel_reason',
                                    event.target.value,
                                )
                            }
                            aria-invalid={Boolean(form.errors.cancel_reason)}
                            autoFocus
                        />
                        <InputError message={form.errors.cancel_reason} />
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Kembali
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Batalkan Pemesanan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
