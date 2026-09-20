# Progress Modul M8 — Driver & Armada / Fleet

**Status:** Selesai (DoD terpenuhi)  
**Referensi:** `docs/PRD_Indogate_v3.1.md` §M8

---

## 1. Ruang Lingkup & Kebutuhan Bisnis

Modul M8 menangani pengelolaan roster driver dan armada kendaraan, penugasan driver & kendaraan ke pemesanan (`package_bookings`), deteksi bentrok jadwal, filter isolasi cabang, preferensi gender driver secara ketat (tanpa silent fallback), serta pencetakan surat tugas resmi operasional.

### Komponen Kunci
1. **Roster Driver (`drivers`)**:
   - `id`, `branch_id`, `name`, `full_name`, `gender` (`male`/`female`), `phone`, `languages` (JSON array: `['id', 'en', 'ar', ...]`), `is_active`, `timestamps`, `softDeletes`.
   - Logging aktivitas pada channel `activity('fleet')`.
2. **Roster Armada Kendaraan (`vehicles`)**:
   - `id`, `branch_id`, `driver_id` (nullable), `plate`, `plate_number`, `type`, `capacity`, `is_active`, `timestamps`, `softDeletes`.
3. **Preferensi Gender Driver (`package_bookings.driver_gender_preference`)**:
   - Nilai: `'female'`, `'male'`, atau `null` (tanpa preferensi).
4. **Penugasan Driver & Kendaraan (`driver_assignments`)**:
   - `id`, `branch_id`, `booking_id`, `driver_id`, `vehicle_id` (opsional), `date_from`, `date_to`, `status` (`assigned`, `in_progress`, `completed`, `cancelled`), `notes`.
5. **Mesin Penugasan (`AssignmentService`)**:
   - `suggestDrivers()`: menyaring driver cabang yang aktif, cocok preferensi gender, dan tidak memiliki penugasan aktif yang beririsan jadwal.
   - `getSuggestionWarning()`: memunculkan pesan peringatan eksplisit jika tidak ada driver yang cocok dengan preferensi gender (tidak pernah menugaskan driver gender berbeda secara diam-diam).
   - `assign()`: memvalidasi isolasi cabang, status aktif, preferensi gender ketat, dan deteksi bentrok jadwal driver maupun armada.
   - `cancel()`: membatalkan penugasan dengan alasan wajib dan tercatat di activity log.
6. **Surat Tugas Operasional (`pdf.duty-letter`)**:
   - Rute bertanda tangan (`signed route`): `/fleet/assignments/{assignment}/duty-letter`.
   - Menampilkan detail driver, kendaraan, jadwal, rincian tamu (manifest), dan instruksi operasional.
   - Setiap akses tercatat pada activity log (`activity('fleet')`).
7. **Antarmuka Livewire**:
   - `DriverList` (`/fleet/drivers`): manajemen roster driver (CRUD, bahasa, status toggle).
   - `VehicleList` (`/fleet/vehicles`): manajemen armada kendaraan (CRUD, kapasitas, status).
   - `AssignmentCalendar` (`/fleet/calendar`): kalender harian penugasan driver, cetak surat tugas, dan pembatalan penugasan.
   - `PackageBookingShow` (`/package-bookings/{id}`): kartu penugasan driver terintegrasi dengan saran otomatis dan peringatan preferensi gender.

---

## 2. Hasil Pengujian Otomatis

Seluruh pengujian otomatis yang disyaratkan PRD M8 telah dibuat dan lulus 100%:

| Berkas Uji | Kasus yang Diuji | Status |
|---|---|---|
| `AssignmentServiceTest.php` | Driver perempuan terpilih saat preferensi perempuan; kosong bila tidak ada driver yang cocok; penolakan ketat jika dipaksakan | Lulus |
| `ConflictDetectionTest.php` | Penugasan tanggal beririsan ditolak (`ScheduleConflictException`); batas tanggal beririsan ditolak; jadwal terpisah diterima; penugasan dibatalkan tidak memicu bentrok; bentrok kendaraan ditolak | Lulus |
| `DriverCrudTest.php` | Driver nonaktif tidak muncul di saran; penugasan driver nonaktif ditolak; soft deleted driver diabaikan; array bahasa driver tersimpan rapi | Lulus |
| `AssignmentPermissionTest.php` | Finance Admin ditolak (`403`); CS Admin & Super Admin diizinkan | Lulus |
| `BranchIsolationTest.php` | Driver Bali tidak muncul untuk pemesanan Jakarta; penugasan lintas cabang ditolak; akses model ditolak policy | Lulus |
| `FleetDutyLetterTest.php` | Akses surat tugas lewat signed URL sukses & tercatat di log aktivitas; akses tanpa signed URL ditolak | Lulus |
| `FleetLivewireTest.php` | CRUD Livewire Driver & Vehicle, kalender penugasan, dan alur penugasan di `PackageBookingShow` | Lulus |

Semua 182 pengujian di seluruh sistem lulus tanpa kegagalan:
```
Tests:    182 passed (432 assertions)
Duration: 28.18s
```

Pint dan terjemahan (`php artisan lang:missing`):
- `vendor/bin/pint --test` passed cleanly.
- `php artisan lang:missing` reports 0 gaps across `id`, `en`, `ar`.

### 2.1 Hasil Pengujian Browser Nyata (Automated Headless Chrome via Puppeteer)
- **Login Sesi Nyata:** CS Admin (User 2) berhasil login dan masuk dashboard.
- **Roster Driver (`/id/admin/fleet/drivers`):** Modal "Tambah Driver" terbuka; berhasil membuat driver perempuan (*Kadek Ayu Dewi*, SIM A) dan driver laki-laki (*I Gede Putu*, SIM B1). Keduanya muncul pada tabel.
- **Armada Kendaraan (`/id/admin/fleet/vehicles`):** Modal "Tambah Kendaraan" terbuka; berhasil membuat kendaraan *Toyota Alphard Luxury* (Plat `DK 8899 AB`, Kapasitas 5). Muncul pada tabel.
- **Workflow Booking Show (`/id/admin/package-bookings/2`):**
  - Preferensi gender diubah menjadi *Driver Perempuan*: Livewire secara reaktif menyaring dropdown menjadi hanya *Kadek Ayu Dewi*.
  - Pemilihan armada: dropdown kendaraan menyajikan *DK 8899 AB — Toyota Alphard Luxury*.
  - Form submit berhasil: status booking card berubah menjadi *Ditugaskan* dengan data driver dan kendaraan lengkap.
- **Surat Tugas / Duty Letter:** Tombol URL ber-tanda tangan digital terverifikasi. Halaman surat tugas menampilkan nomor booking *BK-A6X1VFG4*, *Kadek Ayu Dewi*, dan plat *DK 8899 AB*.
- **Kalender Operasional (`/id/admin/fleet/calendar`):** Membuka tanggal `2026-10-18`, penugasan aktif muncul pada slot tanggal.
- **RBAC Enforcement:** Login sebagai Finance Admin (User 3) lalu mengakses rute armada menghasilkan **HTTP 403 Forbidden** di seluruh endpoint (`/drivers`, `/vehicles`, `/calendar`).

---

## 3. Definition of Done Checklist

- [x] Preferensi gender dihormati, tidak pernah dilanggar diam-diam
- [x] Bentrok jadwal terdeteksi dan ditolak
- [x] Driver terisolasi per cabang
- [x] Kasus tidak ada driver cocok ditangani eksplisit
- [x] Surat tugas (PDF / signed web route) dengan activity log
- [x] Uji browser nyata (Headless Chrome) lulus 100%
- [x] Seluruh test suite lulus 100% (182 tests, 432 assertions)
- [x] Kualitas kode rapi (Pint & Clean Code)
