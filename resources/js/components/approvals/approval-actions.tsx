import { useForm } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
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
import { approve, reject } from '@/routes/approvals';
import type { ActionableApproval } from '@/types';

type Props = {
    approval: ActionableApproval;
    bookingCode: string;
    size?: 'default' | 'sm';
};

/**
 * Tombol Setujui dan Tolak beserta dialog catatannya.
 */
export function ApprovalActions({
    approval,
    bookingCode,
    size = 'default',
}: Props) {
    return (
        <>
            <DecisionDialog
                decision="approve"
                approval={approval}
                bookingCode={bookingCode}
                size={size}
            />
            <DecisionDialog
                decision="reject"
                approval={approval}
                bookingCode={bookingCode}
                size={size}
            />
        </>
    );
}

function DecisionDialog({
    decision,
    approval,
    bookingCode,
    size,
}: Props & { decision: 'approve' | 'reject' }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ note: '' });
    const isApprove = decision === 'approve';
    const isLastLevel = approval.level === approval.total_levels;
    const noteId = `${decision}-note-${approval.id}`;

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.submit(isApprove ? approve(approval.id) : reject(approval.id), {
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
                <Button
                    size={size}
                    variant={isApprove ? 'default' : 'outline'}
                    className={
                        isApprove
                            ? undefined
                            : 'border-destructive-foreground/40 text-destructive-foreground hover:border-destructive-foreground hover:text-destructive-foreground'
                    }
                >
                    {isApprove ? <Check /> : <X />}
                    {isApprove ? 'Setujui' : 'Tolak'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>
                            {isApprove ? 'Setujui' : 'Tolak'} {bookingCode}?
                        </DialogTitle>
                        <DialogDescription>
                            Level {approval.level} dari {approval.total_levels}.{' '}
                            {isApprove
                                ? isLastLevel
                                    ? 'Ini level terakhir, pemesanan akan berstatus Disetujui.'
                                    : `Setelah disetujui, giliran berpindah ke level ${approval.level + 1}.`
                                : 'Pemesanan akan ditolak dan level berikutnya dilewati.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-1.5">
                        <Label htmlFor={noteId}>
                            Catatan {isApprove ? '(opsional)' : ''}
                        </Label>
                        <Textarea
                            id={noteId}
                            rows={3}
                            value={form.data.note}
                            onChange={(event) =>
                                form.setData('note', event.target.value)
                            }
                            placeholder={
                                isApprove
                                    ? 'Mis. pastikan driver cukup istirahat'
                                    : 'Alasan penolakan'
                            }
                            aria-invalid={Boolean(form.errors.note)}
                            autoFocus
                        />
                        <InputError message={form.errors.note} />
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Kembali
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant={isApprove ? 'default' : 'destructive'}
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {isApprove ? 'Setujui' : 'Tolak Pemesanan'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
