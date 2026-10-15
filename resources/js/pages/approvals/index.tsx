import { Head, router } from '@inertiajs/react';
import { Inbox } from 'lucide-react';
import { ApprovalCard } from '@/components/approvals/approval-card';
import Heading from '@/components/heading';
import { LoadingRegion } from '@/components/loading-region';
import { Pagination } from '@/components/pagination';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index } from '@/routes/approvals';
import type { ApprovalIndexProps, ApprovalTab } from '@/types';

const EMPTY_MESSAGES: Record<ApprovalTab, string> = {
    waiting: 'Tidak ada pemesanan yang menunggu persetujuan Anda.',
    queue: 'Tidak ada pemesanan yang masih menunggu level sebelumnya.',
    history: 'Belum ada pemesanan yang Anda setujui, tolak, atau lewati.',
};

export default function ApprovalsIndex({
    tab,
    tabs,
    counts,
    approvals,
}: ApprovalIndexProps) {
    return (
        <>
            <Head title="Persetujuan Pemesanan" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Persetujuan Pemesanan"
                    description="Pemesanan yang menunggu keputusan Anda beserta riwayat persetujuan sebelumnya."
                />

                <Tabs
                    value={tab}
                    onValueChange={(value) =>
                        router.get(
                            index.url({ query: { tab: value } }),
                            {},
                            { preserveScroll: true },
                        )
                    }
                    className="gap-4"
                >
                    <TabsList className="w-full sm:w-fit">
                        {tabs.map((item) => (
                            <TabsTrigger key={item.value} value={item.value}>
                                {item.label}
                                <span className="ml-1 rounded-full bg-muted px-1.5 text-xs tabular-nums">
                                    {counts[item.value]}
                                </span>
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    <TabsContent value={tab}>
                        <LoadingRegion className="grid grid-cols-1 gap-4">
                            {approvals.data.length === 0 ? (
                                <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed px-4 py-12 text-center text-muted-foreground">
                                    <Inbox className="size-8" />
                                    <p className="text-sm">
                                        {EMPTY_MESSAGES[tab]}
                                    </p>
                                </div>
                            ) : (
                                approvals.data.map((approval) => (
                                    <ApprovalCard
                                        key={approval.id}
                                        approval={approval}
                                    />
                                ))
                            )}

                            <Pagination
                                meta={approvals.meta}
                                links={approvals.links}
                            />
                        </LoadingRegion>
                    </TabsContent>
                </Tabs>
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
