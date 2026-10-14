import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index } from '@/routes/reports';

export default function ReportsIndex() {
    return (
        <>
            <Head title="Laporan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Laporan"
                    description="Laporan periodik pemesanan kendaraan dan export ke Excel."
                />
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Laporan',
            href: index(),
        },
    ],
};
