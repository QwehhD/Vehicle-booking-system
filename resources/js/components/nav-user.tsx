import { usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { useIsMobile } from '@/hooks/use-mobile';

/**
 * Avatar pengguna di bagian bawah rail sidebar; membuka menu akun.
 */
export function NavUser() {
    const { auth } = usePage().props;
    const isMobile = useIsMobile();
    const getInitials = useInitials();

    if (!auth.user) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="rounded-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40"
                aria-label={`Menu akun ${auth.user.name}`}
                data-test="sidebar-menu-button"
            >
                <Avatar className="size-10 overflow-hidden rounded-full ring-2 ring-sidebar-border transition-shadow duration-300 hover:ring-brand/60">
                    <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                    <AvatarFallback className="rounded-full bg-brand-soft text-xs font-semibold text-sidebar-accent-foreground">
                        {getInitials(auth.user.name)}
                    </AvatarFallback>
                </Avatar>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="min-w-56 rounded-lg"
                align="end"
                side={isMobile ? 'top' : 'right'}
                sideOffset={12}
            >
                <UserMenuContent user={auth.user} />
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
