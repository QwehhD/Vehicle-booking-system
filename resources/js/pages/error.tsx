import { Head, Link, usePage } from '@inertiajs/react';
import { OrbitArt } from '@/components/orbit-art';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

const COPY: Record<number, { title: string; description: string }> = {
    403: {
        title: 'Akses ditolak',
        description: 'Anda tidak memiliki izin untuk membuka halaman ini.',
    },
    404: {
        title: 'Halaman tidak ditemukan',
        description: 'Halaman yang Anda cari tidak ada atau sudah dipindahkan.',
    },
    500: {
        title: 'Terjadi kesalahan',
        description:
            'Ada masalah di server kami. Silakan coba beberapa saat lagi.',
    },
    503: {
        title: 'Sedang dalam pemeliharaan',
        description:
            'Aplikasi sedang diperbarui. Silakan kembali sebentar lagi.',
    },
};

export default function ErrorPage({
    status,
    message,
}: {
    status: number;
    message: string | null;
}) {
    const { auth } = usePage().props;
    const copy = COPY[status] ?? COPY[500];

    return (
        <>
            <Head title={copy.title} />
            <main className="app-canvas flex min-h-svh flex-col items-center justify-center gap-8 bg-background p-6 text-center">
                <OrbitArt className="reveal w-56 sm:w-64" />
                <div className="grid gap-3 [--stagger:1]">
                    <p className="reveal font-display text-7xl leading-none font-light tracking-tight tabular-nums">
                        <span className="text-gradient">{status}</span>
                    </p>
                    <h1 className="reveal font-display text-2xl font-normal tracking-tight">
                        {copy.title}
                    </h1>
                    <p className="reveal max-w-md text-sm text-muted-foreground">
                        {message ?? copy.description}
                    </p>
                </div>
                <Button
                    asChild
                    variant="outline"
                    className="reveal [--stagger:2]"
                >
                    <Link href={auth.user ? dashboard() : login()}>
                        {auth.user ? 'Kembali ke dashboard' : 'Masuk'}
                    </Link>
                </Button>
            </main>
        </>
    );
}
