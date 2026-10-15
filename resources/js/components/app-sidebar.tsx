import { Link, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    ClipboardCheck,
    FileSpreadsheet,
    History,
    LayoutGrid,
    Truck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
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
import { index as bookingsIndex } from '@/routes/bookings';
import { index as reportsIndex } from '@/routes/reports';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem, UserRole } from '@/types';

function navItemsFor(
    role: UserRole,
    pendingApprovalsCount: number | null,
): NavItem[] {
    const dashboardItem: NavItem = {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    };

    if (role === 'admin') {
        return [
            dashboardItem,
            { title: 'Pemesanan', href: bookingsIndex(), icon: CalendarCheck },
            { title: 'Laporan', href: reportsIndex(), icon: FileSpreadsheet },
            {
                title: 'Log Aktivitas',
                href: activityLogsIndex(),
                icon: History,
            },
            { title: 'Kendaraan', href: vehiclesIndex(), icon: Truck },
        ];
    }

    return [
        dashboardItem,
        {
            title: 'Persetujuan',
            href: approvalsIndex(),
            icon: ClipboardCheck,
            badge: pendingApprovalsCount,
        },
    ];
}

export function AppSidebar() {
    const { auth, pendingApprovalsCount } = usePage().props;
    const mainNavItems = navItemsFor(auth.user.role, pendingApprovalsCount);

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

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
