import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { OrbitArt } from '@/components/orbit-art';
import { TodayDate } from '@/components/today-date';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const FEATURES = [
    'Pemesanan Kendaraan',
    'Persetujuan Berjenjang',
    'Laporan Periodik',
];

/**
 * Layout halaman tamu bergaya editorial: judul bergradasi dan form di kiri,
 * ilustrasi orbit dan tanggal hari ini di kanan.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="app-canvas relative min-h-svh overflow-hidden bg-background">
            <div className="relative mx-auto flex min-h-svh max-w-7xl flex-col px-5 sm:px-10">
                <header className="reveal flex h-20 shrink-0 items-center justify-between gap-4">
                    <Link
                        href={home()}
                        className="flex items-center gap-3 rounded-sm focus-visible:ring-[3px] focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        <span className="flex size-9 items-center justify-center rounded-md bg-brand text-brand-foreground">
                            <AppLogoIcon className="size-4" />
                        </span>
                        <span className="font-display text-sm font-medium tracking-[0.24em] uppercase">
                            Armada
                        </span>
                    </Link>
                    <div className="flex items-center gap-4">
                        <span className="eyebrow hidden sm:inline">{name}</span>
                        <AppearanceToggle />
                    </div>
                </header>

                <main className="grid flex-1 items-center gap-12 py-8 lg:grid-cols-2 lg:py-12">
                    <section className="grid max-w-md gap-10">
                        <div className="grid gap-5">
                            <p className="reveal font-display text-5xl leading-[1.02] font-light tracking-tight [--stagger:1] sm:text-6xl">
                                <span className="text-gradient">
                                    Manajemen Armada Terpadu
                                </span>
                            </p>
                            <p className="reveal max-w-sm text-sm text-muted-foreground [--stagger:2]">
                                Sistem pemesanan, persetujuan berjenjang, dan
                                pemantauan pemakaian kendaraan operasional
                                perusahaan.
                            </p>
                        </div>

                        <div className="reveal grid gap-6 [--stagger:3]">
                            <div className="grid gap-1.5 border-t pt-6">
                                <h1 className="font-display text-lg font-medium tracking-tight">
                                    {title}
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                            {children}
                        </div>
                    </section>

                    <section
                        aria-hidden
                        className="reveal relative hidden items-center justify-center [--stagger:2] lg:flex"
                    >
                        <OrbitArt className="w-full max-w-[32rem]" />
                        <div className="absolute right-0 bottom-0 text-right">
                            <p className="eyebrow mb-3">Hari ini</p>
                            <TodayDate className="text-4xl xl:text-5xl" />
                        </div>
                    </section>
                </main>

                <footer className="reveal shrink-0 [--stagger:4]">
                    <div aria-hidden className="gradient-rule" />
                    <ul className="flex flex-wrap gap-x-8 gap-y-2 py-5">
                        {FEATURES.map((feature, index) => (
                            <li key={feature} className="eyebrow">
                                <span className="mr-2 text-foreground/80 tabular-nums">
                                    0{index + 1}
                                </span>
                                {feature}
                            </li>
                        ))}
                    </ul>
                </footer>
            </div>
        </div>
    );
}
