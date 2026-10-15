import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({ items }: { items: NavItem[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="text-[10px] font-medium tracking-[0.18em] text-sidebar-foreground/45 uppercase">
                Menu
            </SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentOrParentUrl(item.href)}
                            tooltip={{ children: item.title }}
                            className="relative h-10 text-xs font-medium tracking-[0.12em] uppercase before:absolute before:inset-y-2 before:left-0 before:w-0.5 before:scale-y-0 before:bg-gradient-to-b before:from-[var(--grad-1)] before:via-[var(--grad-2)] before:to-[var(--grad-3)] before:transition-transform before:duration-500 before:ease-[var(--ease-out-expo)] group-data-[collapsible=icon]:before:hidden data-[active=true]:before:scale-y-100"
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                        {item.badge ? (
                            <SidebarMenuBadge className="top-2.5 rounded-sm bg-gradient-to-r from-[var(--grad-1)] to-[var(--grad-2)] px-1.5 font-semibold text-sidebar-primary-foreground tabular-nums peer-hover/menu-button:text-sidebar-primary-foreground peer-data-[active=true]/menu-button:text-sidebar-primary-foreground">
                                {item.badge}
                            </SidebarMenuBadge>
                        ) : null}
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
