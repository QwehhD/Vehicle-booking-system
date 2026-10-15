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
 * bergeser halus ke pilihan yang dipilih.
 */
export function AppearanceToggle({ className }: { className?: string }) {
    const { appearance, updateAppearance } = useAppearance();

    const choose = (value: Appearance) => {
        const root = document.documentElement;
        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        if (!document.startViewTransition || reduceMotion) {
            updateAppearance(value);

            return;
        }

        // Warna lama dan baru disilangkan pelan, bukan berganti mendadak.
        root.classList.add('theme-switching');
        void document
            .startViewTransition(() => updateAppearance(value))
            .finished.finally(() => root.classList.remove('theme-switching'));
    };
    const active = Math.max(
        0,
        OPTIONS.findIndex((option) => option.value === appearance),
    );

    return (
        <div
            role="radiogroup"
            aria-label="Tampilan"
            className={cn(
                'relative grid h-8 grid-cols-3 rounded-md border bg-background/60 p-0.5',
                className,
            )}
        >
            <span
                aria-hidden
                className="absolute inset-y-0.5 left-0.5 w-[calc((100%-4px)/3)] rounded-sm bg-accent shadow-xs transition-transform duration-300 ease-[var(--ease-out-expo)]"
                style={{ transform: `translateX(${active * 100}%)` }}
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
                        'relative z-10 flex w-8 items-center justify-center rounded-sm text-muted-foreground transition-colors duration-200 outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/40',
                        appearance === value && 'text-foreground',
                    )}
                >
                    <Icon className="size-3.5" />
                </button>
            ))}
        </div>
    );
}
