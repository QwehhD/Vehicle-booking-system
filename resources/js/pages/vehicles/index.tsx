import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index } from '@/routes/vehicles';

export default function VehiclesIndex() {
    return (
        <>
            <Head title="Kendaraan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Kendaraan"
                    description="Daftar kendaraan, jadwal service, dan riwayat pemakaian."
                />
            </div>
        </>
    );
}

VehiclesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Kendaraan',
            href: index(),
        },
    ],
};
