import { Truck } from 'lucide-react';
import { useId } from 'react';
import type { CSSProperties } from 'react';
import { cn } from '@/lib/utils';

type OrbitStyle = CSSProperties & {
    '--orbit-duration'?: string;
    '--delay'?: string;
};

const STARS = [
    [118, 96, 1],
    [452, 404, 0.8],
    [70, 236, 0.6],
    [470, 210, 0.7],
    [300, 476, 0.9],
] as const;

/**
 * Ilustrasi orbit garis tipis dengan planet pastel dan pesawat kertas yang
 * melayang pelan, mengikuti gaya desain referensi. Dekoratif saja.
 */
export function OrbitArt({ className }: { className?: string }) {
    const id = useId();
    const gradient = `${id}-gradient`;
    const glow = `${id}-glow`;

    return (
        <svg
            viewBox="0 0 520 520"
            fill="none"
            aria-hidden
            className={cn('overflow-visible text-foreground', className)}
        >
            <defs>
                <linearGradient id={gradient} x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stopColor="var(--grad-1)" />
                    <stop offset="50%" stopColor="var(--grad-2)" />
                    <stop offset="100%" stopColor="var(--grad-3)" />
                </linearGradient>
                <radialGradient id={glow}>
                    <stop
                        offset="0%"
                        stopColor="var(--grad-2)"
                        stopOpacity="0.22"
                    />
                    <stop
                        offset="100%"
                        stopColor="var(--grad-2)"
                        stopOpacity="0"
                    />
                </radialGradient>
            </defs>

            <circle cx="260" cy="260" r="210" fill={`url(#${glow})`} />

            <g
                className="orbit-spin"
                style={{ '--orbit-duration': '80s' } as OrbitStyle}
            >
                <g transform="rotate(-18 260 260)">
                    <ellipse
                        cx="260"
                        cy="260"
                        rx="236"
                        ry="82"
                        stroke={`url(#${gradient})`}
                        strokeWidth="1.25"
                    />
                    <circle cx="496" cy="260" r="9" fill="var(--grad-2)" />
                </g>
            </g>

            <g
                className="orbit-spin orbit-spin-reverse"
                style={{ '--orbit-duration': '110s' } as OrbitStyle}
            >
                <g transform="rotate(34 260 260)">
                    <ellipse
                        cx="260"
                        cy="260"
                        rx="200"
                        ry="122"
                        stroke="currentColor"
                        strokeOpacity="0.2"
                    />
                    <circle cx="60" cy="260" r="6" fill="var(--grad-3)" />
                </g>
            </g>

            <g
                className="orbit-spin"
                style={{ '--orbit-duration': '140s' } as OrbitStyle}
            >
                <g transform="rotate(72 260 260)">
                    <ellipse
                        cx="260"
                        cy="260"
                        rx="158"
                        ry="54"
                        stroke="currentColor"
                        strokeOpacity="0.32"
                        strokeDasharray="2 7"
                    />
                    <circle cx="260" cy="206" r="4" fill="var(--grad-1)" />
                </g>
            </g>

            <circle
                cx="260"
                cy="260"
                r="92"
                fill="var(--card)"
                stroke="currentColor"
                strokeOpacity="0.16"
            />
            <circle
                cx="260"
                cy="260"
                r="104"
                stroke={`url(#${gradient})`}
                strokeOpacity="0.6"
                strokeDasharray="1 5"
            />
            <Truck
                x="224"
                y="224"
                size={72}
                strokeWidth={0.9}
                className="text-foreground"
            />

            <g
                className="orbit-float"
                style={{ '--delay': '-2s' } as OrbitStyle}
            >
                <circle
                    cx="422"
                    cy="104"
                    r="15"
                    fill="var(--grad-3)"
                    fillOpacity="0.85"
                />
                <ellipse
                    cx="422"
                    cy="104"
                    rx="28"
                    ry="7"
                    transform="rotate(-20 422 104)"
                    stroke="var(--grad-2)"
                    strokeWidth="1.25"
                />
            </g>

            <g
                className="orbit-float"
                style={{ '--delay': '-4.5s' } as OrbitStyle}
            >
                <path
                    d="M84 392 L140 370 L122 404 L112 392 Z"
                    fill="var(--grad-2)"
                    fillOpacity="0.9"
                />
                <path
                    d="M112 392 L140 370"
                    stroke="currentColor"
                    strokeOpacity="0.4"
                />
            </g>

            <g
                className="orbit-float"
                style={{ '--delay': '-1s' } as OrbitStyle}
            >
                <rect
                    x="404"
                    y="372"
                    width="16"
                    height="16"
                    rx="2"
                    transform="rotate(24 412 380)"
                    fill="var(--grad-1)"
                    fillOpacity="0.85"
                />
            </g>

            {STARS.map(([x, y, scale]) => (
                <path
                    key={`${x}-${y}`}
                    d="M0 -7 L1.6 -1.6 L7 0 L1.6 1.6 L0 7 L-1.6 1.6 L-7 0 L-1.6 -1.6 Z"
                    transform={`translate(${x} ${y}) scale(${scale})`}
                    fill="currentColor"
                    fillOpacity="0.55"
                />
            ))}
        </svg>
    );
}
