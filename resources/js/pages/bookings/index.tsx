import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index } from '@/routes/bookings';

export default function BookingsIndex() {
    return (
        <>
            <Head title="Pemesanan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Pemesanan"
                    description="Daftar pemesanan kendaraan beserta progres persetujuannya."
                />
            </div>
        </>
    );
}

BookingsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pemesanan',
            href: index(),
        },
    ],
};
