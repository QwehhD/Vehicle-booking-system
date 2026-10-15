const TIME_ZONE = 'Asia/Jakarta';

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'Mei',
    'Jun',
    'Jul',
    'Agt',
    'Sep',
    'Okt',
    'Nov',
    'Des',
];

const MONTHS_LONG = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const weekdayFormatter = new Intl.DateTimeFormat('id-ID', {
    timeZone: TIME_ZONE,
    weekday: 'long',
});

const partsFormatter = new Intl.DateTimeFormat('en-GB', {
    timeZone: TIME_ZONE,
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

function parts(value: string | Date) {
    const date = typeof value === 'string' ? new Date(value) : value;
    const result: Record<string, string> = {};

    for (const part of partsFormatter.formatToParts(date)) {
        result[part.type] = part.value;
    }

    return {
        year: result.year,
        month: Number(result.month),
        day: Number(result.day),
        hour: result.hour,
        minute: result.minute,
    };
}

/**
 * Bagian tanggal WIB untuk tampilan besar, mis. 15 / Oktober / 2026.
 */
export function longDateParts(value: string | Date) {
    const date = typeof value === 'string' ? new Date(value) : value;
    const { year, month, day } = parts(date);

    return {
        weekday: weekdayFormatter.format(date),
        day: String(day),
        month: MONTHS_LONG[month - 1],
        year,
        iso: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
    };
}

/**
 * Tanggal dan jam WIB, mis. "14 Okt 2026, 09:00".
 */
export function formatDateTime(value: string | Date | null): string {
    if (!value) {
        return '–';
    }

    const { year, month, day, hour, minute } = parts(value);

    return `${day} ${MONTHS[month - 1]} ${year}, ${hour}:${minute}`;
}

/**
 * Tanggal WIB tanpa jam, mis. "14 Okt 2026".
 */
export function formatDate(value: string | Date | null): string {
    if (!value) {
        return '–';
    }

    const { year, month, day } = parts(value);

    return `${day} ${MONTHS[month - 1]} ${year}`;
}

/**
 * Jam WIB, mis. "09:00".
 */
export function formatTime(value: string | Date): string {
    const { hour, minute } = parts(value);

    return `${hour}:${minute}`;
}

/**
 * Rentang jadwal; tanggal akhir disingkat bila sama dengan tanggal mulai.
 */
export function formatSchedule(startAt: string, endAt: string): string {
    if (formatDate(startAt) === formatDate(endAt)) {
        return `${formatDateTime(startAt)} – ${formatTime(endAt)}`;
    }

    return `${formatDateTime(startAt)} – ${formatDateTime(endAt)}`;
}

const numberFormatter = new Intl.NumberFormat('id-ID');

/**
 * Angka dengan pemisah ribuan Indonesia, mis. "84.120" atau "15,5".
 */
export function formatNumber(value: number | null, fractionDigits = 0): string {
    if (value === null) {
        return '–';
    }

    return fractionDigits === 0
        ? numberFormatter.format(value)
        : new Intl.NumberFormat('id-ID', {
              minimumFractionDigits: fractionDigits,
              maximumFractionDigits: fractionDigits,
          }).format(value);
}

/**
 * Jarak dalam kilometer, mis. "1.234 km".
 */
export function formatKm(value: number): string {
    return `${formatNumber(Math.round(value))} km`;
}

/**
 * Volume BBM dalam liter dengan dua desimal, mis. "29,40 L".
 */
export function formatLiters(value: number): string {
    return `${formatNumber(value, 2)} L`;
}

/**
 * Sisa hari menuju suatu jadwal, mis. "4 hari lagi" atau "Terlambat 2 hari".
 */
export function formatDaysUntil(days: number): string {
    if (days < 0) {
        return `Terlambat ${Math.abs(days)} hari`;
    }

    return days === 0 ? 'Hari ini' : `${days} hari lagi`;
}
