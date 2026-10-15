import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    CalendarCheck,
    ClipboardCheck,
    FileSpreadsheet,
    History,
    LayoutDashboard,
    LayoutGrid,
    Plus,
    Route,
    Truck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { NavMain } from '@/components/nav-main';
import type { NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import { Sidebar, useSidebar } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as approvalsIndex } from '@/routes/approvals';
import {
    create as bookingsCreate,
    index as bookingsIndex,
} from '@/routes/bookings';
import { index as reportsIndex } from '@/routes/reports';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem, UserRole } from '@/types';

function navGroupsFor(
    role: UserRole,
    pendingApprovalsCount: number | null,
): NavGroup[] {
    const overview: NavGroup = {
        label: 'Ringkasan',
        icon: LayoutDashboard,
        items: [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }],
    };

    if (role === 'admin') {
        return [
            overview,
            {
                label: 'Operasional',
                icon: Route,
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
                label: 'Laporan & Audit',
                icon: BarChart3,
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
            label: 'Persetujuan',
            icon: ClipboardCheck,
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

type RailLink = {
    key: string;
    label: string;
    href: NavItem['href'];
    icon: LucideIcon;
    active: boolean;
};

/**
 * Rail ikon di sisi kiri: logo, modul (kelompok menu), pemilih tema, dan
 * avatar. Saat sidebar diciutkan, rail menampilkan setiap menu agar semua
 * halaman tetap bisa dijangkau.
 */
function AppRail({ groups }: { groups: NavGroup[] }) {
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const collapsed = state === 'collapsed' && !isMobile;

    const links: RailLink[] = collapsed
        ? groups.flatMap((group) =>
              group.items.map((item) => ({
                  key: item.title,
                  label: item.title,
                  href: item.href,
                  icon: item.icon ?? group.icon,
                  active: isCurrentOrParentUrl(item.href),
              })),
          )
        : groups.map((group) => ({
              key: group.label,
              label: group.label,
              href: group.items[0].href,
              icon: group.icon,
              active: group.items.some((item) =>
                  isCurrentOrParentUrl(item.href),
              ),
          }));

    return (
        <div className="flex w-16 shrink-0 flex-col items-center py-3">
            <Link
                href={dashboard()}
                prefetch
                aria-label="Armada — ke Dashboard"
                className="group/logo flex size-10 items-center justify-center rounded-xl bg-brand text-brand-foreground shadow-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40"
            >
                <AppLogoIcon className="size-5 transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover/logo:-rotate-6" />
            </Link>

            <nav
                aria-label={collapsed ? 'Menu' : 'Modul'}
                className="mt-8 flex flex-col items-center gap-2"
            >
                {links.map((link) => (
                    <Tooltip key={link.key}>
                        <TooltipTrigger asChild>
                            <Link
                                href={link.href}
                                prefetch
                                aria-label={link.label}
                                className={cn(
                                    'flex size-10 items-center justify-center rounded-full transition-[background-color,color,box-shadow] duration-300 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
                                    link.active
                                        ? 'bg-brand text-brand-foreground shadow-sm'
                                        : 'text-sidebar-foreground/65 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                                )}
                            >
                                <link.icon className="size-[18px]" />
                            </Link>
                        </TooltipTrigger>
                        <TooltipContent side="right">
                            {link.label}
                        </TooltipContent>
                    </Tooltip>
                ))}
            </nav>

            <div className="mt-auto flex flex-col items-center gap-4">
                <div aria-hidden className="h-px w-8 bg-sidebar-border" />
                <AppearanceToggle orientation="vertical" />
                <NavUser />
            </div>
        </div>
    );
}

/**
 * Pil konteks di atas panel menu. Admin mendapat tombol (+) untuk membuat
 * pemesanan; approver melihat jumlah antrean persetujuannya.
 */
function ContextPill({
    role,
    pendingApprovalsCount,
}: {
    role: UserRole;
    pendingApprovalsCount: number | null;
}) {
    const admin = role === 'admin';

    return (
        <div className="flex h-10 items-center justify-between gap-2 rounded-full border border-sidebar-border bg-sidebar-accent/40 pr-1.5 pl-4">
            <span className="truncate text-[13px] font-semibold text-sidebar-accent-foreground">
                {admin ? 'Administrasi Pool' : 'Panel Persetujuan'}
            </span>
            <Tooltip>
                <TooltipTrigger asChild>
                    <Link
                        href={admin ? bookingsCreate() : approvalsIndex()}
                        aria-label={
                            admin
                                ? 'Buat pemesanan'
                                : `${pendingApprovalsCount ?? 0} menunggu persetujuan`
                        }
                        className="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-semibold text-brand-foreground tabular-nums transition-transform duration-300 ease-[var(--ease-out-expo)] outline-none hover:scale-105 focus-visible:ring-[3px] focus-visible:ring-ring/40"
                    >
                        {admin ? (
                            <Plus className="size-4" />
                        ) : (
                            (pendingApprovalsCount ?? 0)
                        )}
                    </Link>
                </TooltipTrigger>
                <TooltipContent side="right">
                    {admin ? 'Buat pemesanan' : 'Menunggu keputusan Anda'}
                </TooltipContent>
            </Tooltip>
        </div>
    );
}

/**
 * Kartu ajakan di bawah panel menu: rekap laporan untuk admin, antrean
 * persetujuan untuk approver.
 */
function CallToAction({
    role,
    pendingApprovalsCount,
}: {
    role: UserRole;
    pendingApprovalsCount: number | null;
}) {
    const waiting = pendingApprovalsCount ?? 0;

    if (role === 'admin') {
        return (
            <div className="rounded-xl border border-brand/30 bg-brand-soft p-4 text-center">
                <p className="text-[13px] font-semibold text-sidebar-accent-foreground">
                    Rekap Periodik
                </p>
                <p className="mt-1 text-xs text-sidebar-foreground/70">
                    Unduh laporan pemesanan dalam format Excel untuk periode
                    yang dibutuhkan.
                </p>
                <Button
                    asChild
                    size="sm"
                    className="mt-3 w-full rounded-full bg-brand text-brand-foreground hover:bg-brand/90"
                >
                    <Link href={reportsIndex()}>
                        <FileSpreadsheet />
                        Buka Laporan
                    </Link>
                </Button>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-brand/30 bg-brand-soft p-4 text-center">
            <p className="text-[13px] font-semibold text-sidebar-accent-foreground">
                Antrean Persetujuan
            </p>
            <p className="mt-1 text-xs text-sidebar-foreground/70">
                {waiting > 0
                    ? `${waiting} pemesanan menunggu keputusan Anda.`
                    : 'Seluruh persetujuan sudah ditangani.'}
            </p>
            {waiting > 0 && (
                <Button
                    asChild
                    size="sm"
                    className="mt-3 w-full rounded-full bg-brand text-brand-foreground hover:bg-brand/90"
                >
                    <Link href={approvalsIndex()}>Tinjau Sekarang</Link>
                </Button>
            )}
        </div>
    );
}

export function AppSidebar() {
    const { auth, pendingApprovalsCount } = usePage().props;
    const groups = navGroupsFor(auth.user.role, pendingApprovalsCount);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <div className="flex h-full min-h-0 overflow-hidden">
                <AppRail groups={groups} />

                <div className="flex min-w-0 flex-1 flex-col border-l border-sidebar-border px-3 py-3 group-data-[collapsible=icon]:hidden md:w-[12.5rem] md:flex-none">
                    <ContextPill
                        role={auth.user.role}
                        pendingApprovalsCount={pendingApprovalsCount}
                    />
                    <div className="mt-6 min-h-0 flex-1 overflow-y-auto">
                        <NavMain groups={groups} />
                    </div>
                    <CallToAction
                        role={auth.user.role}
                        pendingApprovalsCount={pendingApprovalsCount}
                    />
                </div>
            </div>
        </Sidebar>
    );
}
