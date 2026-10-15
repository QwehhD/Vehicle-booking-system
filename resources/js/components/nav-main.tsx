import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Fragment } from 'react';
import {
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export type NavGroup = {
    label: string;
    icon: LucideIcon;
    items: NavItem[];
};

/**
 * Menu di panel sidebar: item berbentuk pil yang terisi warna aksen saat
 * aktif, dengan garis pemisah antarkelompok.
 */
export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <nav aria-label="Menu utama" className="grid">
            {groups.map((group, index) => (
                <Fragment key={group.label}>
                    {index > 0 && (
                        <div
                            aria-hidden
                            className="mx-3 my-3 h-px bg-sidebar-border"
                        />
                    )}
                    <SidebarMenu className="gap-1" aria-label={group.label}>
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentOrParentUrl(item.href)}
                                    className="h-10 gap-3 rounded-full px-3.5 text-[13px] font-medium text-sidebar-foreground/75 transition-[background-color,color,box-shadow] duration-300 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-brand data-[active=true]:font-semibold data-[active=true]:text-brand-foreground data-[active=true]:shadow-sm data-[active=true]:hover:bg-brand data-[active=true]:hover:text-brand-foreground"
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                                {item.badge ? (
                                    <SidebarMenuBadge className="top-2.5 right-2.5 rounded-full bg-brand px-1.5 font-semibold text-brand-foreground tabular-nums peer-hover/menu-button:text-brand-foreground peer-data-[active=true]/menu-button:bg-brand-foreground peer-data-[active=true]/menu-button:text-brand">
                                        {item.badge}
                                    </SidebarMenuBadge>
                                ) : null}
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </Fragment>
            ))}
        </nav>
    );
}
