import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
}: {
    label: string;
    value: string;
    hint?: string;
    icon: LucideIcon;
}) {
    return (
        <Card className="py-4">
            <CardContent className="flex items-start justify-between gap-3 px-4">
                <div className="grid gap-1">
                    <p className="text-sm text-muted-foreground">{label}</p>
                    <p className="text-2xl font-semibold tracking-tight tabular-nums">
                        {value}
                    </p>
                    {hint && (
                        <p className="text-xs text-muted-foreground">{hint}</p>
                    )}
                </div>
                <Icon className="size-5 shrink-0 text-muted-foreground" />
            </CardContent>
        </Card>
    );
}
