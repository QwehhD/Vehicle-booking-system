import type { BadgeColor, BookingApproval } from '@/types/booking';

export type DashboardFilters = {
    year: number;
    region_id: number | null;
};

export type MonthlyBookings = {
    month: number;
    label: string;
    completed: number;
    approved: number;
    pending: number;
    cancelled: number;
    rejected: number;
};

export type TopVehicle = {
    id: number;
    plate_number: string;
    name: string;
    bookings: number;
    distance: number;
};

export type CompositionSlice = {
    key: string;
    label: string;
    value: number;
};

export type MonthlyUsage = {
    month: number;
    label: string;
    distance: number;
    fuel: number;
};

export type ServiceDueVehicle = {
    id: number;
    plate_number: string;
    name: string;
    location: string;
    status_label: string;
    status_color: BadgeColor;
    next_service_at: string;
    days_left: number;
};

export type AdminDashboard = {
    view: 'admin';
    stats: {
        total: number;
        pending: number;
        approved: number;
        completed_this_month: number;
        vehicles_available: number;
        vehicles_total: number;
        vehicles_service_due: number;
    };
    bookingsPerMonth: MonthlyBookings[];
    topVehicles: TopVehicle[];
    composition: {
        type: CompositionSlice[];
        ownership: CompositionSlice[];
    };
    distanceAndFuel: MonthlyUsage[];
    serviceDue: ServiceDueVehicle[];
};

export type ApproverDashboard = {
    view: 'approver';
    stats: {
        waiting: number;
        approved: number;
        rejected: number;
    };
    waitingApprovals: BookingApproval[];
    bookingsPerMonth: MonthlyBookings[];
    topVehicles: TopVehicle[];
};

export type DashboardProps = {
    filters: DashboardFilters;
    years: number[];
    regions: { id: number; name: string }[];
    dashboard: AdminDashboard | ApproverDashboard;
};
