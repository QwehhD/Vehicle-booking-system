import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-brand text-brand-foreground transition-transform duration-500 ease-[var(--ease-out-expo)] group-hover/logo:-rotate-6">
                <AppLogoIcon className="size-4" />
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate font-display text-sm font-medium tracking-[0.2em] uppercase">
                    Armada
                </span>
                <span className="truncate text-[11px] text-sidebar-foreground/55">
                    {name}
                </span>
            </div>
        </>
    );
}
