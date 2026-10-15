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
import { BookingsPerMonthChart } from '@/components/dashboard/bookings-per-month-chart';
import { DashboardFilters } from '@/components/dashboard/dashboard-filters';
import { DistanceFuelChart } from '@/components/dashboard/distance-fuel-chart';
import { ServiceDueTable } from '@/components/dashboard/service-due-table';
import { StatCard } from '@/components/stat-card';
import { TopVehiclesChart } from '@/components/dashboard/top-vehicles-chart';
import { UsageCompositionChart } from '@/components/dashboard/usage-composition-chart';
import { WaitingApprovalsList } from '@/components/dashboard/waiting-approvals-list';
import Heading from '@/components/heading';
import { formatNumber } from '@/lib/format';
import { dashboard as dashboardRoute } from '@/routes';
import type {
    AdminDashboard as AdminData,
    ApproverDashboard as ApproverData,
    DashboardProps,
} from '@/types';

export default function Dashboard({
    filters,
    years,
    regions,
    dashboard,
}: DashboardProps) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <Heading
                        title="Dashboard"
                        description={`Selamat datang, ${auth.user.name}.`}
                    />
                    <DashboardFilters
                        filters={filters}
                        years={years}
                        regions={regions}
                    />
                </div>

                {dashboard.view === 'admin' ? (
                    <AdminDashboard data={dashboard} year={filters.year} />
                ) : (
                    <ApproverDashboard data={dashboard} year={filters.year} />
                )}
            </div>
        </>
    );
}

function AdminDashboard({ data, year }: { data: AdminData; year: number }) {
    const { stats } = data;

    return (
        <>
            <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
                <StatCard
                    label="Total pemesanan"
                    value={formatNumber(stats.total)}
                    hint={`Tahun ${year}`}
                    icon={CalendarCheck}
                />
                <StatCard
                    label="Menunggu persetujuan"
                    value={formatNumber(stats.pending)}
                    icon={Hourglass}
                />
                <StatCard
                    label="Disetujui"
                    value={formatNumber(stats.approved)}
                    hint="Belum selesai"
                    icon={ClipboardCheck}
                />
                <StatCard
                    label="Selesai bulan ini"
                    value={formatNumber(stats.completed_this_month)}
                    icon={CircleCheck}
                />
                <StatCard
                    label="Kendaraan tersedia"
                    value={`${formatNumber(stats.vehicles_available)}/${formatNumber(stats.vehicles_total)}`}
                    icon={Truck}
                />
                <StatCard
                    label="Service ≤ 14 hari"
                    value={formatNumber(stats.vehicles_service_due)}
                    hint="Kendaraan"
                    icon={Wrench}
                />
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <BookingsPerMonthChart
                    data={data.bookingsPerMonth}
                    year={year}
                    className="xl:col-span-2"
                />
                <UsageCompositionChart
                    type={data.composition.type}
                    ownership={data.composition.ownership}
                />
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <TopVehiclesChart data={data.topVehicles} />
                <DistanceFuelChart data={data.distanceAndFuel} />
            </div>

            <ServiceDueTable vehicles={data.serviceDue} />
        </>
    );
}

function ApproverDashboard({
    data,
    year,
}: {
    data: ApproverData;
    year: number;
}) {
    const { stats } = data;

    return (
        <>
            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Menunggu giliran saya"
                    value={formatNumber(stats.waiting)}
                    icon={CalendarClock}
                />
                <StatCard
                    label="Sudah saya setujui"
                    value={formatNumber(stats.approved)}
                    hint={`Tahun ${year}`}
                    icon={ThumbsUp}
                />
                <StatCard
                    label="Sudah saya tolak"
                    value={formatNumber(stats.rejected)}
                    hint={`Tahun ${year}`}
                    icon={ThumbsDown}
                />
            </div>

            <WaitingApprovalsList approvals={data.waitingApprovals} />

            <div className="grid gap-6 xl:grid-cols-2">
                <BookingsPerMonthChart
                    data={data.bookingsPerMonth}
                    year={year}
                />
                <TopVehiclesChart data={data.topVehicles} />
            </div>
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
