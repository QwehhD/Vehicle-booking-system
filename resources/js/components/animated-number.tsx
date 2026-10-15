import { useEffect, useRef } from 'react';
import { formatNumber } from '@/lib/format';

const DURATION = 1100;

const formatInteger = (value: number) => formatNumber(Math.round(value));

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * Angka yang menghitung naik ke nilainya dengan easing halus. Teks diubah
 * langsung lewat ref agar animasi tidak memicu render ulang React.
 */
export function AnimatedNumber({
    value,
    format = formatInteger,
}: {
    value: number;
    format?: (value: number) => string;
}) {
    const ref = useRef<HTMLSpanElement>(null);
    const current = useRef(0);
    const formatRef = useRef(format);

    useEffect(() => {
        formatRef.current = format;
    }, [format]);

    useEffect(() => {
        const element = ref.current;
        const format = formatRef.current;

        if (!element) {
            return;
        }

        if (prefersReducedMotion()) {
            current.current = value;
            element.textContent = format(value);

            return;
        }

        const from = current.current;
        const start = performance.now();
        let frame = 0;

        const tick = (now: number) => {
            const progress = Math.min(1, (now - start) / DURATION);
            const eased = 1 - Math.pow(1 - progress, 4);

            current.current = from + (value - from) * eased;
            element.textContent = format(current.current);

            if (progress < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);

        return () => cancelAnimationFrame(frame);
    }, [value]);

    return <span ref={ref}>{format(value)}</span>;
}
