import { Head, Link, useForm } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    ApproverFields,
    MIN_APPROVERS,
} from '@/components/bookings/approver-fields';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { create, index, store } from '@/routes/bookings';
import type { BookingCreateProps } from '@/types';

type BookingForm = {
    requester_name: string;
    requester_department: string;
    purpose: string;
    destination: string;
    start_at: string;
    end_at: string;
    vehicle_id: number | null;
    driver_id: number | null;
    approvers: (number | null)[];
};

/**
 * Waktu lokal saat ini dalam format input datetime-local.
 */
function nowForInput(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
}

export default function BookingsCreate({
    vehicles,
    drivers,
    approvers,
}: BookingCreateProps) {
    const form = useForm<BookingForm>({
        requester_name: '',
        requester_department: '',
        purpose: '',
        destination: '',
        start_at: '',
        end_at: '',
        vehicle_id: null,
        driver_id: null,
        approvers: Array<number | null>(MIN_APPROVERS).fill(null),
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.submit(store());
    };

    return (
        <>
            <Head title="Buat Pemesanan" />
            <form
                onSubmit={submit}
                className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4"
            >
                <Heading
                    title="Buat Pemesanan"
                    description="Isi data perjalanan, pilih kendaraan dan driver, lalu tentukan approver berjenjang."
                />

                <Section
                    title="Data Pemohon"
                    description="Pegawai yang mengajukan pemakaian kendaraan."
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            id="requester_name"
                            label="Nama pemohon"
                            error={form.errors.requester_name}
                        >
                            <Input
                                id="requester_name"
                                value={form.data.requester_name}
                                onChange={(event) =>
                                    form.setData(
                                        'requester_name',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    form.errors.requester_name,
                                )}
                            />
                        </Field>
                        <Field
                            id="requester_department"
                            label="Departemen"
                            error={form.errors.requester_department}
                        >
                            <Input
                                id="requester_department"
                                value={form.data.requester_department}
                                onChange={(event) =>
                                    form.setData(
                                        'requester_department',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(
                                    form.errors.requester_department,
                                )}
                            />
                        </Field>
                    </div>
                </Section>

                <Section
                    title="Detail Perjalanan"
                    description="Keperluan, tujuan, dan jadwal pemakaian (WIB)."
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            id="purpose"
                            label="Keperluan"
                            error={form.errors.purpose}
                            className="sm:col-span-2"
                        >
                            <Textarea
                                id="purpose"
                                rows={3}
                                value={form.data.purpose}
                                onChange={(event) =>
                                    form.setData('purpose', event.target.value)
                                }
                                aria-invalid={Boolean(form.errors.purpose)}
                            />
                        </Field>
                        <Field
                            id="destination"
                            label="Tujuan"
                            error={form.errors.destination}
                            className="sm:col-span-2"
                        >
                            <Input
                                id="destination"
                                value={form.data.destination}
                                onChange={(event) =>
                                    form.setData(
                                        'destination',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={Boolean(form.errors.destination)}
                            />
                        </Field>
                        <Field
                            id="start_at"
                            label="Waktu mulai"
                            error={form.errors.start_at}
                        >
                            <Input
                                id="start_at"
                                type="datetime-local"
                                min={nowForInput()}
                                value={form.data.start_at}
                                onChange={(event) =>
                                    form.setData('start_at', event.target.value)
                                }
                                aria-invalid={Boolean(form.errors.start_at)}
                            />
                        </Field>
                        <Field
                            id="end_at"
                            label="Waktu selesai"
                            error={form.errors.end_at}
                        >
                            <Input
                                id="end_at"
                                type="datetime-local"
                                min={form.data.start_at || nowForInput()}
                                value={form.data.end_at}
                                onChange={(event) =>
                                    form.setData('end_at', event.target.value)
                                }
                                aria-invalid={Boolean(form.errors.end_at)}
                            />
                        </Field>
                    </div>
                </Section>

                <Section
                    title="Kendaraan & Driver"
                    description="Kendaraan yang sedang perawatan dan driver nonaktif tidak ditampilkan."
                >
                    <div className="grid gap-4">
                        <Field
                            id="vehicle_id"
                            label="Kendaraan"
                            error={form.errors.vehicle_id}
                        >
                            <SearchableSelect
                                id="vehicle_id"
                                options={vehicles}
                                value={form.data.vehicle_id}
                                onChange={(id) =>
                                    form.setData('vehicle_id', id)
                                }
                                placeholder="Pilih kendaraan"
                                searchPlaceholder="Cari plat, merek, jenis, atau lokasi…"
                                invalid={Boolean(form.errors.vehicle_id)}
                            />
                        </Field>
                        <Field
                            id="driver_id"
                            label="Driver"
                            error={form.errors.driver_id}
                        >
                            <SearchableSelect
                                id="driver_id"
                                options={drivers}
                                value={form.data.driver_id}
                                onChange={(id) => form.setData('driver_id', id)}
                                placeholder="Pilih driver"
                                searchPlaceholder="Cari nama atau lokasi…"
                                invalid={Boolean(form.errors.driver_id)}
                            />
                        </Field>
                    </div>
                </Section>

                <Section
                    title="Approver Berjenjang"
                    description="Urutan menentukan level persetujuan."
                >
                    <ApproverFields
                        options={approvers}
                        value={form.data.approvers}
                        onChange={(value) => form.setData('approvers', value)}
                        errors={form.errors}
                    />
                </Section>

                <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button asChild variant="outline">
                        <Link href={index()}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan Pemesanan
                    </Button>
                </div>
            </form>
        </>
    );
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
                <CardDescription>{description}</CardDescription>
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

function Field({
    id,
    label,
    error,
    className,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('grid gap-1.5', className)}>
            <Label htmlFor={id}>{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

BookingsCreate.layout = {
    breadcrumbs: [
        { title: 'Pemesanan', href: index() },
        { title: 'Buat Pemesanan', href: create() },
    ],
};
