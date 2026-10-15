import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-full border border-sidebar-foreground/30 text-sidebar-foreground transition-colors duration-300 group-hover/logo:border-sidebar-foreground/70">
                <AppLogoIcon className="size-4" />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate font-display text-sm font-medium tracking-[0.24em] uppercase">
                    Armada
                </span>
                <span className="truncate text-[11px] text-sidebar-foreground/55">
                    {name}
                </span>
            </div>
        </>
    );
}
