# Activity Diagram — Pemesanan Kendaraan

Alur pemesanan dari input admin sampai pemesanan selesai, termasuk penolakan dan pembatalan. Setiap _subgraph_ adalah satu swimlane (kiri ke kanan: Admin, Sistem, Approver Level 1, Approver Level 2..N); panah putus-putus menandai perulangan dan cabang opsional.

![Activity diagram](activity-diagram.png)

```mermaid
flowchart LR
    subgraph ADMIN["Admin"]
        A0(["Mulai"])
        A1["Login sebagai admin"]
        A2["Isi form pemesanan:<br/>pemohon, perjalanan, kendaraan,<br/>driver, approver berjenjang"]
        A5["Batalkan pemesanan<br/>dengan alasan"]
        A6["Selesaikan pemesanan:<br/>input odometer awal/akhir & BBM"]
    end

    subgraph SISTEM["Sistem"]
        S0["Validasi: field wajib, waktu,<br/>kendaraan bukan maintenance, driver aktif,<br/>2–5 approver unik, jadwal tidak bentrok"]
        S1{"Valid?"}
        S2["Tampilkan pesan error per field<br/>(menyebut kode booking yang bentrok)"]
        S3["Simpan booking pending, current_level = 1<br/>buat approval level 1..N<br/>catat log booking.created"]
        S4["Approval level 1 = approved<br/>current_level naik<br/>catat log approval.approved"]
        S5{"Level terakhir?"}
        S6["Booking = approved<br/>catat log approval.approved"]
        S7["Approval = rejected, booking = rejected<br/>approval lain yang pending = skipped<br/>catat log approval.rejected"]
        S8["Booking = cancelled<br/>approval yang pending = skipped<br/>catat log booking.cancelled"]
        S9["Booking = completed<br/>odometer kendaraan diperbarui<br/>catat log booking.completed"]
        E1(["Selesai"])
        E2(["Ditolak"])
        E3(["Dibatalkan"])
    end

    subgraph L1["Approver Level 1"]
        B1["Buka Persetujuan → Menunggu Saya"]
        B2{"Setujui atau tolak?"}
    end

    subgraph LN["Approver Level 2..N"]
        C1["Giliran level berikutnya<br/>muncul di Menunggu Saya"]
        C2{"Setujui atau tolak?"}
    end

    A0 --> A1 --> A2 --> S0 --> S1
    S1 -- "valid" --> S3
    S3 --> B1 --> B2
    B2 -- "setujui, catatan opsional" --> S4
    S4 --> C1 --> C2
    C2 -- "setujui" --> S5
    S5 -- "ya" --> S6
    S6 --> A6 --> S9 --> E1
    B2 -- "tolak, catatan wajib" --> S7
    C2 -- "tolak, catatan wajib" --> S7
    S7 --> E2
    S1 -- "tidak valid" --> S2
    S3 -. "selama pending" .-> A5
    S6 -. "selama approved" .-> A5
    A5 --> S8 --> E3
    S2 -. "perbaiki input" .-> A2
    S5 -. "belum: current_level naik,<br/>giliran level berikutnya" .-> C1
```

## Langkah

1. **Admin** login, lalu mengisi form pemesanan: data pemohon, detail perjalanan, kendaraan, driver, dan 2–5 approver yang urutannya menjadi level persetujuan.
2. **Sistem** memvalidasi input (`StoreBookingRequest`) dan memeriksa bentrok jadwal kendaraan dan driver (`BookingService`) di dalam transaksi. Jika gagal, form kembali dengan pesan error per field; pesan bentrok menyebut kode booking yang bentrok.
3. Jika valid, sistem menyimpan booking berstatus `pending` dengan `current_level = 1`, membuat approval level 1..N berstatus `pending`, dan mencatat log `booking.created`.
4. **Approver level 1** melihat pemesanan di tab _Menunggu Saya_. Approval hanya bisa diproses oleh pemiliknya, saat approval dan booking masih `pending`, dan levelnya sama dengan `current_level` (dicek di `ApprovalPolicy`, lalu dicek ulang setelah `lockForUpdate` di `ApprovalService`).
    - **Setujui** → approval `approved`, `current_level` naik, giliran berpindah ke level berikutnya.
    - **Tolak** (catatan wajib) → booking `rejected` dan approval lain yang masih `pending` menjadi `skipped`.
5. **Approver level 2..N** mengulang langkah yang sama. Persetujuan di level terakhir membuat booking `approved`.
6. **Admin** menyelesaikan booking `approved` dengan mengisi odometer awal (bawaan: odometer kendaraan saat ini), odometer akhir (≥ odometer awal), dan BBM terpakai (> 0 liter). Booking menjadi `completed` dan odometer kendaraan diperbarui.
7. **Cabang pembatalan:** selama booking masih `pending` atau sudah `approved`, admin dapat membatalkannya dengan alasan wajib. Booking menjadi `cancelled` dan approval yang masih `pending` menjadi `skipped`.

Setiap langkah yang mengubah data tercatat di tabel `activity_logs` dan di `storage/logs/activity-*.log`.
