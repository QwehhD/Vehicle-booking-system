import { Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    ClipboardCheck,
    FileSpreadsheet,
    History,
    LayoutGrid,
    Plus,
    Truck,
} from 'lucide-react';
import { AnimatedNumber } from '@/components/animated-number';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import type { NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as approvalsIndex } from '@/routes/approvals';
import {
    create as bookingsCreate,
    index as bookingsIndex,
} from '@/routes/bookings';
import { index as reportsIndex } from '@/routes/reports';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { UserRole } from '@/types';

function navGroupsFor(
    role: UserRole,
    pendingApprovalsCount: number | null,
): NavGroup[] {
    const overview: NavGroup = {
        label: 'Ringkasan',
        items: [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }],
    };

    if (role === 'admin') {
        return [
            overview,
            {
                label: 'Operasional',
                items: [
                    {
                        title: 'Pemesanan',
                        href: bookingsIndex(),
                        icon: CalendarCheck,
                    },
                    { title: 'Kendaraan', href: vehiclesIndex(), icon: Truck },
                ],
            },
            {
                label: 'Laporan & audit',
                items: [
                    {
                        title: 'Laporan',
                        href: reportsIndex(),
                        icon: FileSpreadsheet,
                    },
                    {
                        title: 'Log Aktivitas',
                        href: activityLogsIndex(),
                        icon: History,
                    },
                ],
            },
        ];
    }

    return [
        overview,
        {
            label: 'Tugas saya',
            items: [
                {
                    title: 'Persetujuan',
                    href: approvalsIndex(),
                    icon: ClipboardCheck,
                    badge: pendingApprovalsCount,
                },
            ],
        },
    ];
}

/**
 * Kartu aksi cepat di bawah menu: buat pemesanan untuk admin, jumlah
 * antrean persetujuan untuk approver. Disembunyikan saat sidebar diciutkan.
 */
function QuickAction({
    role,
    pendingApprovalsCount,
}: {
    role: UserRole;
    pendingApprovalsCount: number | null;
}) {
    const waiting = pendingApprovalsCount ?? 0;

    return (
        <div className="mx-2 mb-1 rounded-lg border border-sidebar-border bg-sidebar-accent/50 p-3 group-data-[collapsible=icon]:hidden">
            {role === 'admin' ? (
                <>
                    <p className="text-[13px] font-medium text-sidebar-accent-foreground">
                        Pemesanan baru
                    </p>
                    <p className="mt-1 text-xs text-sidebar-foreground/60">
                        Catat permintaan kendaraan beserta approver
                        berjenjangnya.
                    </p>
                    <Button asChild size="sm" className="mt-3 w-full">
                        <Link href={bookingsCreate()}>
                            <Plus />
                            Buat pemesanan
                        </Link>
                    </Button>
                </>
            ) : (
                <>
                    <p className="text-[10px] font-medium tracking-[0.16em] text-sidebar-foreground/50 uppercase">
                        Giliran Anda
                    </p>
                    <p className="mt-1 font-display text-3xl font-light text-sidebar-accent-foreground tabular-nums">
                        <AnimatedNumber value={waiting} />
                    </p>
                    <p className="text-xs text-sidebar-foreground/60">
                        {waiting > 0
                            ? 'pemesanan menunggu keputusan Anda.'
                            : 'Tidak ada pemesanan yang menunggu.'}
                    </p>
                    {waiting > 0 && (
                        <Button
                            asChild
                            size="sm"
                            variant="outline"
                            className="mt-3 w-full"
                        >
                            <Link href={approvalsIndex()}>
                                Buka persetujuan
                            </Link>
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}

export function AppSidebar() {
    const { auth, pendingApprovalsCount } = usePage().props;
    const groups = navGroupsFor(auth.user.role, pendingApprovalsCount);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="group/logo"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-1 pt-2">
                <NavMain groups={groups} />
            </SidebarContent>

            <SidebarFooter className="gap-3">
                <QuickAction
                    role={auth.user.role}
                    pendingApprovalsCount={pendingApprovalsCount}
                />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
