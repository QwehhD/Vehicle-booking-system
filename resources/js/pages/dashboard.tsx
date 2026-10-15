import { Head, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    CalendarClock,
    CircleCheck,
    ClipboardCheck,
    Hourglass,
    ThumbsDown,
    ThumbsUp,
    Truck,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { BookingsPerMonthChart } from '@/components/dashboard/bookings-per-month-chart';
import { DashboardFilters } from '@/components/dashboard/dashboard-filters';
import { DistanceFuelChart } from '@/components/dashboard/distance-fuel-chart';
import { ServiceDueTable } from '@/components/dashboard/service-due-table';
import { TopVehiclesChart } from '@/components/dashboard/top-vehicles-chart';
import { UsageCompositionChart } from '@/components/dashboard/usage-composition-chart';
import { WaitingApprovalsList } from '@/components/dashboard/waiting-approvals-list';
import { LoadingRegion } from '@/components/loading-region';
import { StatCard, StatGrid } from '@/components/stat-card';
import { TodayDate } from '@/components/today-date';
import { formatNumber } from '@/lib/format';
import { dashboard as dashboardRoute } from '@/routes';
import type {
    AdminDashboard as AdminData,
    ApproverDashboard as ApproverData,
    DashboardFilters as Filters,
    DashboardProps,
} from '@/types';

export default function Dashboard({
    filters,
    years,
    regions,
    dashboard,
}: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-8 p-4 md:p-8">
                {dashboard.view === 'admin' ? (
                    <AdminDashboard
                        data={dashboard}
                        filters={filters}
                        years={years}
                        regions={regions}
                    />
                ) : (
                    <ApproverDashboard
                        data={dashboard}
                        filters={filters}
                        years={years}
                        regions={regions}
                    />
                )}
            </div>
        </>
    );
}

type FilterProps = {
    filters: Filters;
    years: number[];
    regions: { id: number; name: string }[];
};

/**
 * Pembuka dashboard bergaya editorial: sapaan, judul bergradasi, filter,
 * dan tanggal hari ini. Kartu ringkasan di bawahnya meredup saat filter
 * memuat ulang data, filter tetap bisa dipakai.
 */
function Hero({
    headline,
    summary,
    children,
    filters,
    years,
    regions,
}: FilterProps & {
    headline: string;
    summary: string;
    children: ReactNode;
}) {
    const { auth } = usePage().props;

    return (
        <section className="grid gap-8">
            <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div className="grid gap-5">
                    <p className="eyebrow reveal">
                        Selamat datang, {auth.user.name}
                    </p>
                    <h1 className="reveal max-w-3xl font-display text-4xl leading-[1.04] font-light tracking-tight [--stagger:1] sm:text-5xl xl:text-6xl">
                        <span className="text-gradient">{headline}</span>
                    </h1>
                    <p className="reveal max-w-lg text-sm text-muted-foreground [--stagger:2]">
                        {summary}
                    </p>
                    <div className="reveal [--stagger:3]">
                        <DashboardFilters
                            filters={filters}
                            years={years}
                            regions={regions}
                        />
                    </div>
                </div>
                <div className="reveal hidden text-right [--stagger:2] lg:block">
                    <p className="eyebrow mb-3">Hari ini</p>
                    <TodayDate className="text-4xl xl:text-5xl" />
                </div>
            </div>
            <div aria-hidden className="gradient-rule" />
            <LoadingRegion>{children}</LoadingRegion>
        </section>
    );
}

function AdminDashboard({
    data,
    ...filterProps
}: FilterProps & { data: AdminData }) {
    const { stats } = data;
    const year = filterProps.filters.year;

    return (
        <>
            <Hero
                {...filterProps}
                headline="Seluruh armada, satu layar."
                summary={`Ringkasan pemesanan, persetujuan, dan pemakaian kendaraan tahun ${year}.`}
            >
                <StatGrid className="grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                    <StatCard
                        index={1}
                        label="Total pemesanan"
                        value={stats.total}
                        hint={`Tahun ${year}`}
                        icon={CalendarCheck}
                    />
                    <StatCard
                        index={2}
                        label="Menunggu persetujuan"
                        value={stats.pending}
                        icon={Hourglass}
                    />
                    <StatCard
                        index={3}
                        label="Disetujui"
                        value={stats.approved}
                        hint="Belum selesai"
                        icon={ClipboardCheck}
                    />
                    <StatCard
                        index={4}
                        label="Selesai bulan ini"
                        value={stats.completed_this_month}
                        icon={CircleCheck}
                    />
                    <StatCard
                        index={5}
                        label="Kendaraan tersedia"
                        value={`${formatNumber(stats.vehicles_available)}/${formatNumber(stats.vehicles_total)}`}
                        icon={Truck}
                    />
                    <StatCard
                        index={6}
                        label="Service ≤ 14 hari"
                        value={stats.vehicles_service_due}
                        hint="Kendaraan"
                        icon={Wrench}
                    />
                </StatGrid>
            </Hero>

            <LoadingRegion className="flex flex-col gap-6">
                <div className="grid gap-6 xl:grid-cols-3">
                    <BookingsPerMonthChart
                        data={data.bookingsPerMonth}
                        year={year}
                        className="xl:col-span-2"
                        index={3}
                    />
                    <TopVehiclesChart data={data.topVehicles} index={4} />
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <UsageCompositionChart
                        type={data.composition.type}
                        ownership={data.composition.ownership}
                        index={5}
                    />
                    <DistanceFuelChart data={data.distanceAndFuel} index={6} />
                </div>

                <ServiceDueTable vehicles={data.serviceDue} />
            </LoadingRegion>
        </>
    );
}

function ApproverDashboard({
    data,
    ...filterProps
}: FilterProps & { data: ApproverData }) {
    const { stats } = data;
    const year = filterProps.filters.year;

    return (
        <>
            <Hero
                {...filterProps}
                headline="Persetujuan Anda, tanpa antre."
                summary={`Pemesanan yang menunggu giliran Anda dan riwayat keputusan tahun ${year}.`}
            >
                <StatGrid className="sm:grid-cols-3">
                    <StatCard
                        index={1}
                        label="Menunggu giliran saya"
                        value={stats.waiting}
                        icon={CalendarClock}
                    />
                    <StatCard
                        index={2}
                        label="Sudah saya setujui"
                        value={stats.approved}
                        hint={`Tahun ${year}`}
                        icon={ThumbsUp}
                    />
                    <StatCard
                        index={3}
                        label="Sudah saya tolak"
                        value={stats.rejected}
                        hint={`Tahun ${year}`}
                        icon={ThumbsDown}
                    />
                </StatGrid>
            </Hero>

            <LoadingRegion className="flex flex-col gap-6">
                <WaitingApprovalsList approvals={data.waitingApprovals} />

                <div className="grid gap-6 xl:grid-cols-2">
                    <BookingsPerMonthChart
                        data={data.bookingsPerMonth}
                        year={year}
                        index={3}
                    />
                    <TopVehiclesChart data={data.topVehicles} index={4} />
                </div>
            </LoadingRegion>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboardRoute(),
        },
    ],
};
