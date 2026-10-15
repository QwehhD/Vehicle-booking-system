import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { ActivityLogDialog } from '@/components/activity-logs/activity-log-dialog';
import { ActivityLogFilters } from '@/components/activity-logs/activity-log-filters';
import Heading from '@/components/heading';
import { LoadingRegion } from '@/components/loading-region';
import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { index } from '@/routes/activity-logs';
import type { ActivityLog, ActivityLogIndexProps } from '@/types';

export default function ActivityLogsIndex({
    logs,
    filters,
    users,
    actions,
}: ActivityLogIndexProps) {
    const [selected, setSelected] = useState<ActivityLog | null>(null);
    const hasFilters = Object.values(filters).some((value) => value !== null);

    return (
        <>
            <Head title="Log Aktivitas Sistem" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Log Aktivitas Sistem"
                    description="Jejak audit seluruh aktivitas pengguna. Pilih baris untuk melihat detailnya."
                />

                <ActivityLogFilters
                    filters={filters}
                    users={users}
                    actions={actions}
                />

                <LoadingRegion className="reveal overflow-hidden rounded-lg border bg-card [--stagger:2]">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Waktu</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead>Aksi</TableHead>
                                <TableHead>Deskripsi</TableHead>
                                <TableHead>IP</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="h-32 text-center text-muted-foreground"
                                    >
                                        {hasFilters
                                            ? 'Tidak ada aktivitas yang cocok dengan filter.'
                                            : 'Belum ada aktivitas.'}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                logs.data.map((log) => (
                                    <TableRow
                                        key={log.id}
                                        tabIndex={0}
                                        className="cursor-pointer"
                                        onClick={() => setSelected(log)}
                                        onKeyDown={(event) => {
                                            if (
                                                event.key === 'Enter' ||
                                                event.key === ' '
                                            ) {
                                                event.preventDefault();
                                                setSelected(log);
                                            }
                                        }}
                                        aria-label={`Detail aktivitas: ${log.description}`}
                                    >
                                        <TableCell className="whitespace-nowrap tabular-nums">
                                            {formatDateTime(log.created_at)}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap">
                                            {log.user?.name ?? (
                                                <span className="text-muted-foreground">
                                                    Tamu
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                label={log.action_label}
                                                color={log.action_color}
                                            />
                                        </TableCell>
                                        <TableCell className="min-w-64 whitespace-normal">
                                            {log.description}
                                        </TableCell>
                                        <TableCell className="whitespace-nowrap text-muted-foreground tabular-nums">
                                            {log.ip_address ?? '–'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </LoadingRegion>

                <Pagination meta={logs.meta} links={logs.links} />
            </div>

            <ActivityLogDialog
                log={selected}
                onClose={() => setSelected(null)}
            />
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
