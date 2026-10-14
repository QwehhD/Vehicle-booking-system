import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { index } from '@/routes/activity-logs';

export default function ActivityLogsIndex() {
    return (
        <>
            <Head title="Log Aktivitas" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Log Aktivitas"
                    description="Riwayat aktivitas pengguna di aplikasi."
                />
            </div>
        </>
    );
}

ActivityLogsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Log Aktivitas',
            href: index(),
        },
    ],
};
