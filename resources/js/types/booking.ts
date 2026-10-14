export type BadgeColor =
    | 'amber'
    | 'blue'
    | 'red'
    | 'slate'
    | 'emerald'
    | 'violet'
    | 'orange';

export type BookingStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'cancelled'
    | 'completed';

export type ApprovalStatus = 'pending' | 'approved' | 'rejected' | 'skipped';

export type VehicleType = 'passenger' | 'cargo';

export type Option = {
    value: string;
    label: string;
};

export type SelectOption = {
    value: number;
    label: string;
    description?: string | null;
};

export type Vehicle = {
    id: number;
    plate_number: string;
    name: string;
    type: VehicleType;
    type_label: string;
    ownership: 'owned' | 'rented';
    ownership_label: string;
    rental_company: string | null;
    odometer: number;
    status: 'available' | 'maintenance';
    status_label: string;
    status_color: BadgeColor;
    last_service_at: string | null;
    next_service_at: string | null;
    location?: {
        id: number;
        name: string;
        region: { id: number; name: string } | null;
    };
};

export type Driver = {
    id: number;
    name: string;
    phone: string;
    license_number: string;
    is_active: boolean;
    location?: { id: number; name: string };
};

export type BookingApproval = {
    id: number;
    level: number;
    status: ApprovalStatus;
    status_label: string;
    status_color: BadgeColor;
    note: string | null;
    acted_at: string | null;
    approver?: { id: number; name: string; position: string | null };
};

export type Booking = {
    id: number;
    code: string;
    requester_name: string;
    requester_department: string;
    purpose: string;
    destination: string;
    start_at: string;
    end_at: string;
    status: BookingStatus;
    status_label: string;
    status_color: BadgeColor;
    current_level: number;
    approval_progress?: { approved: number; total: number };
    odometer_start: number | null;
    odometer_end: number | null;
    distance: number | null;
    fuel_used: number | null;
    completed_at: string | null;
    cancel_reason: string | null;
    cancelled_at: string | null;
    created_at: string;
    vehicle?: Vehicle;
    driver?: Driver;
    creator?: { id: number; name: string };
    approvals?: BookingApproval[];
};

export type ActivityLog = {
    id: number;
    action: string;
    description: string;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user?: { id: number; name: string } | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type BookingFilters = {
    search: string | null;
    status: BookingStatus | null;
    vehicle_type: VehicleType | null;
    region_id: number | null;
    date_from: string | null;
    date_to: string | null;
};

export type BookingIndexProps = {
    bookings: Paginated<Booking>;
    filters: BookingFilters;
    statuses: Option[];
    vehicleTypes: Option[];
    regions: { id: number; name: string }[];
};

export type BookingCreateProps = {
    vehicles: SelectOption[];
    drivers: SelectOption[];
    approvers: SelectOption[];
};

export type BookingShowProps = {
    booking: Booking;
    activityLogs: ActivityLog[];
    can: {
        cancel: boolean;
        complete: boolean;
    };
};
