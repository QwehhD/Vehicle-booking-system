import type { ReactNode } from 'react';
import { useNavigationLoading } from '@/hooks/use-navigation-loading';
import { cn } from '@/lib/utils';

/**
 * Pembungkus data yang meredup selama halaman memuat ulang hasil filter.
 */
export function LoadingRegion({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const loading = useNavigationLoading();

    return (
        <div
            aria-busy={loading}
            className={cn(
                'min-w-0 transition-opacity',
                loading && 'pointer-events-none opacity-60',
                className,
            )}
        >
            {children}
        </div>
    );
}
