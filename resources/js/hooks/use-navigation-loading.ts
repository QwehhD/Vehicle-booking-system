import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * True selama kunjungan Inertia ke halaman yang sama sedang berjalan,
 * mis. saat filter atau pagination diganti, untuk meredupkan data lama.
 */
export function useNavigationLoading(): boolean {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', (event) => {
            if (!event.detail.visit.prefetch) {
                setLoading(true);
            }
        });
        const offFinish = router.on('finish', () => setLoading(false));

        return () => {
            offStart();
            offFinish();
        };
    }, []);

    return loading;
}
