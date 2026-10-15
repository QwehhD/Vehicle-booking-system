import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'error':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return AppLayout;
        }
    },
    defaults: {
        // Animasi antarhalaman hanya untuk navigasi ke halaman lain, bukan
        // untuk kunjungan yang mempertahankan state seperti filter dan tab.
        visitOptions: (_href, options) => ({
            ...options,
            viewTransition:
                options.viewTransition ||
                (options.method === 'get' && !options.preserveState),
        }),
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
