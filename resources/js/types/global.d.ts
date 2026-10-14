import type { Auth } from '@/types/auth';
import type { Flash } from '@/types/ui';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            flash: Flash;
            pendingApprovalsCount: number | null;
            [key: string]: unknown;
        };
    }
}
