import { usePage } from '@inertiajs/react';
import type { CSSProperties, ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    // Rail ikon 4rem ditambah panel menu; saat diciutkan hanya rail.
    return (
        <SidebarProvider
            defaultOpen={isOpen}
            style={
                {
                    '--sidebar-width': '17.5rem',
                    '--sidebar-width-icon': '4rem',
                } as CSSProperties
            }
        >
            {children}
        </SidebarProvider>
    );
}
