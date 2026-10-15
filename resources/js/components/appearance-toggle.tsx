import { Monitor, Moon, Sun } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useAppearance } from '@/hooks/use-appearance';
import type { Appearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

const OPTIONS: { value: Appearance; label: string; icon: LucideIcon }[] = [
    { value: 'light', label: 'Mode terang', icon: Sun },
    { value: 'dark', label: 'Mode gelap', icon: Moon },
    { value: 'system', label: 'Ikuti sistem', icon: Monitor },
];

/**
 * Pemilih tampilan terang, gelap, atau mengikuti sistem. Penanda aktif
 * bergeser halus ke pilihan yang dipilih, dan pergantian warnanya
 * disilangkan pelan lewat View Transitions.
 */
export function AppearanceToggle({
    orientation = 'horizontal',
    className,
}: {
    orientation?: 'horizontal' | 'vertical';
    className?: string;
}) {
    const { appearance, updateAppearance } = useAppearance();
    const vertical = orientation === 'vertical';
    const active = Math.max(
        0,
        OPTIONS.findIndex((option) => option.value === appearance),
    );

    const choose = (value: Appearance) => {
        const root = document.documentElement;
        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (!document.startViewTransition || reduceMotion) {
            updateAppearance(value);

            return;
        }

        root.classList.add('theme-switching');
        void document
            .startViewTransition(() => updateAppearance(value))
            .finished.finally(() => root.classList.remove('theme-switching'));
    };

    return (
        <div
            role="radiogroup"
            aria-label="Tampilan"
            aria-orientation={orientation}
            className={cn(
                'relative grid border p-0.5',
                vertical
                    ? 'w-9 grid-rows-3 gap-0 rounded-full bg-sidebar-accent/40'
                    : 'h-8 grid-cols-3 rounded-md bg-background/60',
                className,
            )}
        >
            <span
                aria-hidden
                className={cn(
                    'absolute shadow-xs transition-transform duration-300 ease-[var(--ease-out-expo)]',
                    vertical
                        ? 'inset-x-0.5 top-0.5 h-[calc((100%-4px)/3)] rounded-full bg-brand'
                        : 'inset-y-0.5 left-0.5 w-[calc((100%-4px)/3)] rounded-sm bg-accent',
                )}
                style={{
                    transform: vertical
                        ? `translateY(${active * 100}%)`
                        : `translateX(${active * 100}%)`,
                }}
            />
            {OPTIONS.map(({ value, label, icon: Icon }) => (
                <button
                    key={value}
                    type="button"
                    role="radio"
                    aria-checked={appearance === value}
                    aria-label={label}
                    title={label}
                    onClick={() => choose(value)}
                    className={cn(
                        'relative z-10 flex items-center justify-center text-muted-foreground transition-colors duration-200 outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/40',
                        vertical ? 'size-8 rounded-full' : 'w-8 rounded-sm',
                        appearance === value &&
                            (vertical
                                ? 'text-brand-foreground hover:text-brand-foreground'
                                : 'text-foreground'),
                    )}
                >
                    <Icon className="size-3.5" />
                </button>
            ))}
        </div>
    );
}
