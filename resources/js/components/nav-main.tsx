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

export type NavGroup = {
    label: string;
    items: NavItem[];
};

/**
 * Menu sidebar per kelompok. Ikon tiap menu berada di kotak kecil yang
 * terisi warna aksen saat menu aktif.
 */
export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <>
            {groups.map((group) => (
                <SidebarGroup key={group.label} className="px-2 py-1">
                    <SidebarGroupLabel className="text-[10px] font-medium tracking-[0.16em] text-sidebar-foreground/45 uppercase">
                        {group.label}
                    </SidebarGroupLabel>
                    <SidebarMenu className="gap-0.5">
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentOrParentUrl(item.href)}
                                    tooltip={{ children: item.title }}
                                    className="group/menu-button h-10 gap-3 rounded-md px-1.5 text-[13px] font-medium text-sidebar-foreground/80 group-data-[collapsible=icon]:p-1! hover:text-sidebar-accent-foreground data-[active=true]:font-semibold"
                                >
                                    <Link href={item.href} prefetch>
                                        {item.icon && (
                                            <span className="flex size-7 shrink-0 items-center justify-center rounded-md border border-sidebar-border bg-sidebar text-sidebar-foreground/70 transition-colors duration-300 group-hover/menu-button:text-sidebar-accent-foreground group-data-[active=true]/menu-button:border-transparent group-data-[active=true]/menu-button:bg-brand group-data-[active=true]/menu-button:text-brand-foreground">
                                                <item.icon className="size-4" />
                                            </span>
                                        )}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                                {item.badge ? (
                                    <SidebarMenuBadge className="top-2.5 rounded-sm bg-brand px-1.5 font-semibold text-brand-foreground tabular-nums peer-hover/menu-button:text-brand-foreground peer-data-[active=true]/menu-button:text-brand-foreground">
                                        {item.badge}
                                    </SidebarMenuBadge>
                                ) : null}
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
