/**
 * Warna grafik dari palet kategorikal tervalidasi (skrip validator data-viz,
 * mode terang dan gelap). Urutan tumpukan status di bawah ini adalah urutan
 * yang lolos uji pasangan bersebelahan; jangan diubah tanpa validasi ulang.
 */
export const SERIES = {
    blue: { light: '#2a78d6', dark: '#3987e5' },
    orange: { light: '#eb6834', dark: '#d95926' },
    aqua: { light: '#1baf7a', dark: '#199e70' },
    yellow: { light: '#eda100', dark: '#c98500' },
    violet: { light: '#6250d6', dark: '#9085e9' },
    red: { light: '#e34948', dark: '#e66767' },
} as const;

/**
 * Status pemesanan dari bawah ke atas pada grafik bertumpuk.
 */
export const BOOKING_STATUS_SERIES = [
    { key: 'completed', label: 'Selesai', theme: SERIES.aqua },
    { key: 'approved', label: 'Disetujui', theme: SERIES.blue },
    { key: 'pending', label: 'Menunggu Persetujuan', theme: SERIES.yellow },
    { key: 'cancelled', label: 'Dibatalkan', theme: SERIES.violet },
    { key: 'rejected', label: 'Ditolak', theme: SERIES.red },
] as const;
