import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
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
            <main className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 text-center">
                <AppLogoIcon className="size-10 text-muted-foreground" />
                <div className="grid gap-2">
                    <p className="text-sm font-medium text-muted-foreground tabular-nums">
                        {status}
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {copy.title}
                    </h1>
                    <p className="max-w-md text-sm text-muted-foreground">
                        {message ?? copy.description}
                    </p>
                </div>
                <Button asChild>
                    <Link href={auth.user ? dashboard() : login()}>
                        {auth.user ? 'Kembali ke dashboard' : 'Masuk'}
                    </Link>
                </Button>
            </main>
        </>
    );
}
