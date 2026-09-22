# 06. Keamanan, Pengujian, dan Operasional — Indogate

Dokumen ini merangkum postur keamanan, kepatuhan hukum perlindungan data pribadi, arsitektur pengujian otomatis, dan panduan operasional sistem.

---

## 1. Kepatuhan UU Perlindungan Data Pribadi (UU PDP No. 27/2022)

Sebagai platform perjalanan internasional yang menangani data identitas sensitif wisatawan mancanegara, Indogate mematuhi regulasi UU PDP melalui langkah-langkah teknis berikut:

### 1.1 Enkripsi Data Pribadi Sensitif (PII at Rest)
- Kolom nomor paspor tamu pada tabel `booking_guests` dan `customers` dienkripsi simetris menggunakan algoritma AES-256-CBC bawaan Laravel (`$casts['passport_number'] = 'encrypted'`).
- Di dalam basis data mentah, nomor paspor hanya tersimpan dalam bentuk ciphertext terenkripsi dan didekripsi otomatis saat dipanggil dalam aplikasi.

### 1.2 Penyimpanan dan Akses Berkas Privat
- Berkas paspor tamu, kuitansi bukti transfer bank, dan surat dinas pengemudi disimpan pada direktori privat lokal (`storage/app/private/`).
- Berkas-berkas tersebut **tidak pernah** dibuka ke folder publik `public/storage`.
- Akses berkas hanya dapat dilakukan melalui rute bertanda tangan sementara (*temporary signed URLs*) yang memiliki batas waktu kedaluwarsa (10–30 menit).
- Akses ke endpoint berkas privat dibatasi oleh *rate limiter* (maksimal 20 request per menit) dan secara otomatis mencatat jejak audit pengunduhan ke tabel `activity_log`. Pengunduhan berkas paspor secara massal (*bulk download*) diblokir secara teknis.

### 1.3 Hak untuk Dihapus (*Right to be Forgotten*)
- Modul retensi data (`App\Domain\Security\Services\DataRetentionService`) menyediakan fungsi:
  - `anonymizeCustomer($customer)`: Mengaburkan data nama, email, dan telepon pelanggan menjadi nilai hash anonim atas permintaan resmi tamu.
  - `purgeExpiredGuestDocuments()`: Menghapus secara permanen berkas paspor digital tamu setelah masa retensi operasional (misal 90 hari pasca kepulangan tur selesai) tanpa merusak catatan pembukuan transaksi.

---

## 2. Klasifikasi Insiden Keamanan dan Respons Tanggap Darurat

Berdasarkan `docs/security/INCIDENT_RESPONSE.md`, insiden dibagi menjadi 3 tingkat keparahan:

| Tingkat | Kriteria Insiden | Batas Waktu Respon | Tindakan Utama |
|---|---|---|---|
| **P1 — Kritis** | Manipulasi saldo bank/bukti bayar, kebocoran data paspor massal, kompromi akun Super Admin, eksekusi kode remote. | < 15 Menit | Karantina darurat (`php artisan down --secret=...`), penonaktifan akun tersangka, rotasi kunci rahasia (`APP_KEY`), dan pelaporan 72 jam ke otoritas UU PDP. |
| **P2 — Mayor** | Akses lintas cabang tidak sah, kegagalan isolasi data cabang (*cross-branch read*), serangan *brute force* persisten. | < 1 Jam | Penonaktifan akun terdampak, pemblokiran IP, audit izin role Spatie, dan perbaikan policy gate. |
| **P3 — Minor** | Percobaan eksploitasi yang berhasil dicegat oleh *rate limiter*, kejanggalan format log non-kritis. | < 4 Jam | Penyesuaian konfigurasi *rate limit* dan pemantauan berkala log keamanan. |

---

## 3. Struktur dan Eksekusi Test Suite (Testing Architecture)

Sistem Indogate memiliki cakupan pengujian komprehensif yang menjamin seluruh alur operasional berjalan sesuai PRD:

### 3.1 Status Pengujian Saat Ini
- **Total Pengujian:** **262 test** (100% Passed).
- **Total Assertion:** **800 assertion**.
- **Waktu Eksekusi:** ~126 detik.
- **Konfigurasi Lingkungan Uji:** Berjalan di atas koneksi basis data SQLite in-memory yang terisolasi dari database pengembangan lokal (`phpunit.xml`).

### 3.2 Pembagian Berkas Pengujian (`tests/`)
1. **Unit Tests (`tests/Unit/` - 13 berkas):**
   - Menguji presisi logika bisnis murni tanpa dependensi request HTTP: kalkulasi harga dan pembulatan sen (`MoneyTest`), penskalaan pax kamar vs aktivitas (`PackagePaxTest`), resolusi aturan margin musiman (`RuleResolverTest`), mesin status booking (`BookingStateMachineTest`), dan deteksi bentrok jadwal supir (`AssignmentServiceTest`).
2. **Feature Tests (`tests/Feature/` - 63 berkas):**
   - Menguji interaksi penuh antar-komponen Livewire, otorisasi peran, validasi formulir, pengunduhan berkas signed, dan ekspor CSV:
     - `SegregationOfDutiesTest`, `BranchScopeTest`, `AuthorizationSweepTest`
     - `PackageCatalogTest`, `BuilderTest`, `MarginVisibilityTest`
     - `CreateQuotationTest`, `RateLockTest`, `ExpiryTest`
     - `PaymentVerificationTest`, `SelfApprovalTest`, `ReconciliationTest`
     - `ConflictDetectionTest`, `FleetDutyLetterTest`
     - `EncryptionTest`, `SignedUrlTest`, `DataRetentionTest`

### 3.3 Cara Menjalankan Pengujian
Gunakan executable PHP yang terpasang di sistem:
```bash
# Menjalankan seluruh test suite
"C:\laragon\bin\php\php-8.3.29-Win32-vs16-x64\php.exe" artisan test

# Menjalankan pengujian domain tertentu (contoh: Finance)
"C:\laragon\bin\php\php-8.3.29-Win32-vs16-x64\php.exe" artisan test --filter=Finance

# Menjalankan linter kode Laravel Pint
"C:\laragon\bin\php\php-8.3.29-Win32-vs16-x64\php.exe" vendor/bin/pint --test
```

---

## 4. Perintah Terjadwal (Scheduler & Background Commands)

Perintah terjadwal yang dikonfigurasi pada `bootstrap/app.php` dan `app/Console/Commands`:

1. **`quotations:expire` (Dijalankan setiap menit):**
   Mengubah status penawaran harga (`Quotation`) yang telah melewati batas `valid_until` menjadi `expired`.
2. **`bookings:transition-status` (Dijalankan setiap jam):**
   Memeriksa tanggal keberangkatan dan kepulangan tamu, lalu memajukan status booking yang relevan dari `paid` menjadi `in_progress`, serta dari `in_progress` menjadi `completed`.
3. **`indogate:purge-expired` (Dijalankan harian):**
   Mengeksekusi kebijakan retensi data untuk menghapus berkas privat kadaluwarsa sesuai ketentuan hukum.
4. **`indogate:backup` (Dijalankan harian):**
   Membuat snapshot cadangan basis data terenkripsi ke dalam direktori `storage/app/backups/`.
5. **`lang:missing` (Utilitas developer):**
   Memindai string terjemahan antarmuka yang belum terdaftar di kamus bahasa (`lang/{id,en,ar}`).
