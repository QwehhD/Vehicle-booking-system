import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index } from '@/routes/approvals';

export default function ApprovalsIndex() {
    return (
        <>
            <Head title="Persetujuan" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Persetujuan"
                    description="Pemesanan yang menunggu atau pernah melewati persetujuan Anda."
                />
            </div>
        </>
    );
}

ApprovalsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Persetujuan',
            href: index(),
        },
    ],
};
