import { StatusBadge } from '@/components/status-badge';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { BookingApproval } from '@/types';

const DOT_CLASSES: Record<BookingApproval['status'], string> = {
    pending: 'border-amber-400 bg-background',
    approved: 'border-emerald-500 bg-emerald-500',
    rejected: 'border-red-500 bg-red-500',
    skipped:
        'border-slate-300 bg-slate-200 dark:border-slate-600 dark:bg-slate-700',
};

/**
 * Timeline persetujuan per level, dari level 1 ke level terakhir.
 */
export function ApprovalTimeline({
    approvals,
    currentLevel,
    isPending,
}: {
    approvals: BookingApproval[];
    currentLevel: number;
    isPending: boolean;
}) {
    return (
        <ol className="relative grid gap-6">
            {approvals.map((approval, index) => {
                const isCurrent = isPending && approval.level === currentLevel;

                return (
                    <li key={approval.id} className="relative flex gap-3">
                        {index < approvals.length - 1 && (
                            <span
                                aria-hidden
                                className="absolute top-5 left-[7px] h-[calc(100%+0.5rem)] w-px bg-border"
                            />
                        )}
                        <span
                            aria-hidden
                            className={cn(
                                'relative mt-1 size-[15px] shrink-0 rounded-full border-2',
                                DOT_CLASSES[approval.status],
                                isCurrent && 'ring-4 ring-amber-400/25',
                            )}
                        />
                        <div className="grid min-w-0 flex-1 gap-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Level {approval.level}
                                </span>
                                <StatusBadge
                                    label={
                                        isCurrent
                                            ? 'Menunggu giliran'
                                            : approval.status_label
                                    }
                                    color={approval.status_color}
                                />
                            </div>
                            <div className="font-medium">
                                {approval.approver?.name}
                            </div>
                            {approval.approver?.position && (
                                <div className="text-sm text-muted-foreground">
                                    {approval.approver.position}
                                </div>
                            )}
                            {approval.note && (
                                <p className="rounded-md bg-muted px-3 py-2 text-sm">
                                    “{approval.note}”
                                </p>
                            )}
                            {approval.acted_at && (
                                <div className="text-xs text-muted-foreground">
                                    {formatDateTime(approval.acted_at)}
                                </div>
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
