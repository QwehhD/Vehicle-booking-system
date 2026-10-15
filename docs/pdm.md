# Physical Data Model — Pemesanan Kendaraan

Model data fisik fitur pemesanan kendaraan. Tipe kolom mengikuti skema MySQL/MariaDB yang dihasilkan migration (`bigint` = `bigint unsigned`, `varchar` = `varchar(255)` kecuali disebut lain). Semua tabel memakai `id` auto increment dan kolom `created_at`/`updated_at`.

![Physical data model](pdm.png)

```mermaid
erDiagram
    regions ||--|{ locations : "memiliki"
    locations |o--o{ users : "penempatan"
    locations ||--o{ drivers : "basis"
    locations ||--o{ vehicles : "pool"
    vehicles ||--o{ bookings : "dipesan"
    drivers ||--o{ bookings : "bertugas"
    users ||--o{ bookings : "membuat (created_by)"
    bookings ||--|{ booking_approvals : "berjenjang"
    users ||--o{ booking_approvals : "menyetujui (approver_id)"
    users |o--o{ activity_logs : "melakukan"
    bookings |o--o{ activity_logs : "subject (polimorfik)"

    regions {
        bigint id PK
        varchar name
        timestamp created_at
        timestamp updated_at
    }

    locations {
        bigint id PK
        bigint region_id FK
        varchar name
        enum type "head_office | branch_office | mine"
        varchar address "nullable"
        timestamp created_at
        timestamp updated_at
    }

    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at "nullable"
        varchar password
        enum role "admin | approver, default approver"
        varchar position "nullable, jabatan"
        bigint location_id FK "nullable, on delete set null"
        varchar remember_token "nullable, varchar(100)"
        timestamp created_at
        timestamp updated_at
    }

    drivers {
        bigint id PK
        varchar name
        varchar phone
        varchar license_number
        bigint location_id FK
        boolean is_active "tinyint(1), default 1"
        timestamp created_at
        timestamp updated_at
    }

    vehicles {
        bigint id PK
        varchar plate_number UK
        varchar name "merek dan model"
        enum type "passenger | cargo"
        enum ownership "owned | rented"
        varchar rental_company "nullable, wajib bila rented"
        bigint location_id FK "lokasi pool"
        int odometer "unsigned, default 0"
        enum status "available | maintenance, default available"
        date last_service_at "nullable"
        date next_service_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    bookings {
        bigint id PK
        varchar code UK "BK-YYYYMM-NNNNN"
        varchar requester_name
        varchar requester_department
        text purpose
        varchar destination
        bigint vehicle_id FK
        bigint driver_id FK
        bigint created_by FK "users.id"
        datetime start_at
        datetime end_at
        enum status "pending | approved | rejected | cancelled | completed"
        tinyint current_level "unsigned, default 1"
        int odometer_start "unsigned, nullable"
        int odometer_end "unsigned, nullable"
        decimal fuel_used "decimal(8,2), liter, nullable"
        datetime completed_at "nullable"
        text cancel_reason "nullable"
        datetime cancelled_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    booking_approvals {
        bigint id PK
        bigint booking_id FK "on delete cascade"
        bigint approver_id FK "users.id"
        tinyint level "unsigned"
        enum status "pending | approved | rejected | skipped"
        text note "nullable"
        datetime acted_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    activity_logs {
        bigint id PK
        bigint user_id FK "nullable, on delete set null"
        varchar action "mis. booking.created"
        varchar subject_type "nullable, alias morph"
        bigint subject_id "nullable"
        varchar description
        json properties "nullable"
        varchar ip_address "nullable, varchar(45)"
        text user_agent "nullable"
        timestamp created_at
        timestamp updated_at
    }
```

## Penjelasan tabel

| Tabel               | Isi                                                                                                                                                                                                               | Kunci & indeks                                                                                                                                                                              |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `regions`           | Wilayah operasional: Sulawesi Tenggara, Sulawesi Tengah, Maluku Utara.                                                                                                                                            | PK `id`.                                                                                                                                                                                    |
| `locations`         | Kantor pusat, kantor cabang, dan tambang di setiap region. `type` membedakan ketiganya.                                                                                                                           | FK `region_id` → `regions`.                                                                                                                                                                 |
| `users`             | Akun aplikasi. `role` membedakan admin (pool) dan approver; `position` adalah jabatan yang tampil di daftar approver.                                                                                             | Unik `email`; FK `location_id` → `locations` (nullable, _set null_).                                                                                                                        |
| `drivers`           | Pengemudi beserta lokasi basisnya. Hanya driver `is_active` yang bisa dipilih saat memesan.                                                                                                                       | FK `location_id` → `locations`.                                                                                                                                                             |
| `vehicles`          | Kendaraan angkutan orang/barang, milik perusahaan atau sewa. `odometer` diperbarui saat pemesanan diselesaikan; `next_service_at` dipakai untuk peringatan service.                                               | Unik `plate_number`; FK `location_id` → `locations`.                                                                                                                                        |
| `bookings`          | Pemesanan kendaraan. `current_level` menunjuk level approval yang sedang menunggu giliran; kolom odometer, BBM, dan `completed_at` terisi saat selesai; `cancel_reason` dan `cancelled_at` saat dibatalkan.       | Unik `code`; FK `vehicle_id`, `driver_id`, `created_by` (→ `users`); indeks `(vehicle_id, start_at, end_at)` dan `(driver_id, start_at, end_at)` untuk cek bentrok jadwal; indeks `status`. |
| `booking_approvals` | Satu baris per level persetujuan. Level 1..N dibuat bersamaan dengan booking; urutannya adalah urutan approver di form.                                                                                           | FK `booking_id` (_cascade_), `approver_id` (→ `users`); unik `(booking_id, level)` dan `(booking_id, approver_id)` sehingga satu approver tidak muncul dua kali.                            |
| `activity_logs`     | Log aplikasi untuk setiap proses: login/logout/gagal login, pembuatan, persetujuan, penolakan, pembatalan, penyelesaian pemesanan, dan export laporan. `subject_type` memakai alias morph (`booking`, `user`, …). | FK `user_id` (→ `users`, _set null_); indeks `(subject_type, subject_id)`, `action`, `created_at`.                                                                                          |

## Aturan integritas di level aplikasi

- Jadwal kendaraan dan driver tidak boleh beririsan dengan booking lain berstatus `pending`/`approved` (`start_at < end_baru` dan `end_at > start_baru`). Dicek di `BookingService` di dalam transaksi setelah baris kendaraan dan driver dikunci (`lockForUpdate`).
- Status approval selalu konsisten dengan status booking: booking `approved`/`completed` berarti semua approval `approved`; booking `rejected` berarti satu approval `rejected` dan sisanya `skipped`; booking `cancelled` membuat approval yang masih `pending` menjadi `skipped`.
- `bookings.code` dibentuk dari bulan pembuatan dan `id` (`BK-202610-00012`) di dalam transaksi yang sama dengan insert.

## Tabel bawaan Laravel

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, dan `migrations` berasal dari migration bawaan Laravel dan tidak termasuk model data pemesanan.
