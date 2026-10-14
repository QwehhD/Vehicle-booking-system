import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

/**
 * Menampilkan flash message dari shared props `flash` sebagai toast.
 *
 * Event `success` tidak terpicu saat navigasi riwayat (back/forward),
 * sehingga pesan yang sama tidak muncul ulang.
 */
export function useFlashToast(): void {
    useEffect(() => {
        return router.on('success', (event) => {
            const { flash } = event.detail.page.props;

            if (flash?.success) {
                toast.success(flash.success);
            }

            if (flash?.error) {
                toast.error(flash.error);
            }
        });
    }, []);
}
