import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

/**
 * Navigasi halaman untuk koleksi resource Laravel yang dipaginasi.
 */
export function Pagination({ meta, links }: Omit<Paginated<unknown>, 'data'>) {
    if (meta.last_page <= 1) {
        return null;
    }

    const pages = meta.links.slice(1, -1);

    return (
        <nav
            aria-label="Navigasi halaman"
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
        >
            <p className="text-sm text-muted-foreground">
                Menampilkan {meta.from}–{meta.to} dari {meta.total} data
            </p>
            <div className="flex flex-wrap items-center gap-1">
                <PageButton href={links.prev} label="Halaman sebelumnya">
                    <ChevronLeft />
                </PageButton>
                {pages.map((page, index) =>
                    page.url === null ? (
                        <span
                            key={`gap-${index}`}
                            className="px-2 text-sm text-muted-foreground"
                        >
                            …
                        </span>
                    ) : (
                        <Button
                            key={page.label}
                            asChild
                            size="sm"
                            variant={page.active ? 'default' : 'outline'}
                            className="min-w-9"
                        >
                            <Link
                                href={page.url}
                                preserveScroll
                                aria-current={page.active ? 'page' : undefined}
                            >
                                {page.label}
                            </Link>
                        </Button>
                    ),
                )}
                <PageButton href={links.next} label="Halaman berikutnya">
                    <ChevronRight />
                </PageButton>
            </div>
        </nav>
    );
}

function PageButton({
    href,
    label,
    children,
}: {
    href: string | null;
    label: string;
    children: React.ReactNode;
}) {
    if (href === null) {
        return (
            <Button size="sm" variant="outline" disabled aria-label={label}>
                {children}
            </Button>
        );
    }

    return (
        <Button asChild size="sm" variant="outline" aria-label={label}>
            <Link href={href} preserveScroll>
                {children}
            </Link>
        </Button>
    );
}
