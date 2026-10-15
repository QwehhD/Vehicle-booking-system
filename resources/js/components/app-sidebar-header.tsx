import { CalendarDays } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { longDateParts } from '@/lib/format';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function HeaderDate() {
    const { weekday, day, month, year, iso } = longDateParts(new Date());

    return (
        <time
            dateTime={iso}
            className="hidden items-center gap-2 rounded-md border bg-background/60 px-2.5 py-1 text-xs text-muted-foreground md:flex"
        >
            <CalendarDays className="size-3.5" />
            {weekday}, {day} {month} {year}
        </time>
    );
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 rounded-t-[inherit] border-b bg-background/80 px-4 backdrop-blur-md transition-[width,height] duration-300 ease-[var(--ease-out-expo)] group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6">
            <div className="flex min-w-0 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Separator orientation="vertical" className="mr-1 h-4!" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <HeaderDate />
            <div
                aria-hidden
                className="gradient-rule absolute inset-x-0 -bottom-px opacity-70"
            />
        </header>
    );
}
