# Rencana Role "Admin Cabang" & Meja Admin

Status: **rencana, belum dikerjakan** · Dibuat: 2026-09-23

## 1. Tujuan

Memberi tangan kanan Mr. Ahmed (Head Cabang Bali) satu tempat untuk menjalankan operasional harian cabang dan mengawasi tim CS, tanpa harus memegang akun Super Admin.

## 2. Siapa pengguna

| Peran | Orang | Fokus |
|-------|-------|-------|
| Super Admin | Mr. Ahmed | Pemilik keputusan: harga/margin, user, pengaturan, semua cabang |
| **Admin Cabang** (baru) | Tangan kanan Mr. Ahmed | Operasional harian cabang: tim CS, booking, armada, katalog |
| CS Admin | Tim CS | Lead → quotation → booking (Meja CS) |
| Finance Admin | Finance | Verifikasi pembayaran, piutang, vendor, margin |

## 3. Yang sudah ada (hasil audit)

- Permission (`RolesAndPermissionsSeeder`): `catalog.manage`, `pricing.manage`, `currency.manage`, `lead.manage`, `quotation.create`, `booking.manage`, `payment.verify`, `driver.assign`, `report.margin.view`, `report.view`, `user.manage`, `activitylog.view`, `branch.switch`.
- **Hak Akses Role** (`RoleMatrix`): role baru bisa dibuat dari UI. Aturan pemisahan tugas (`booking.manage` + `payment.verify` tidak boleh bersamaan, kecuali Super Admin) sudah ditegakkan.
- Semua data terkunci per cabang (`BranchScope`). Pindah cabang butuh `branch.switch`.
- **Meja CS** (`Admin\Desk\CsDesk`): antrian lead, quotation, tagihan, keberangkatan, dan pencarian. Bisa dipakai ulang.
- **Dashboard eksekutif + Laporan** (`report.view`), laporan margin (`report.margin.view`).
- Lead punya `assigned_to` + `follow_up_at`. Belum ada assign massal di daftar lead.
- Booking: ubah status dan **batal langsung** (`PackageBookingShow::cancel`, wajib alasan). Belum ada alur persetujuan.
- Armada: kalender penugasan, driver, kendaraan (`driver.assign`), cegah bentrok (`AssignmentService`).
- Paket: toggle "Tampilkan di storefront" di Package Builder. Rate punya `valid_to`.
- `user.manage` bersifat **global**: daftar user tidak difilter per cabang.
- Audit log (`activitylog.view`).
- Override harga hanya ada di Pricing Simulator. **Belum ada** alur diskon/persetujuan harga di quotation.

## 4. Desain

### 4.1 Role & permission

**Admin Cabang**:
`lead.manage`, `quotation.create`, `booking.manage`, `driver.assign`, `catalog.manage`, `report.view`, `activitylog.view`

Tetap di **Super Admin (Mr. Ahmed)**: `pricing.manage` (margin/harga), `currency.manage`, `user.manage`, `branch.switch`.
Tetap di **Finance**: `payment.verify`.

Kenapa Admin **tidak** memegang `payment.verify`? Admin memegang `booking.manage`, jadi dia yang mencatat transaksi. Kalau dia juga yang memverifikasi uang masuk, satu orang bisa membuat dan menyetujui pembayaran sendiri. Ini dicegah oleh aturan pemisahan tugas yang sudah ada. Pembayaran tetap diverifikasi Finance.

`report.margin.view` (melihat modal & margin): **keputusan owner** (lihat §8).

Role dibuat lewat seeder (supaya ada di semua environment) dan tetap bisa diubah dari Hak Akses Role.

### 4.2 Halaman "Meja Admin" (Pusat Kendali Cabang)

Route `admin.control` (izin `booking.manage` + `lead.manage`). Livewire `Admin\Desk\AdminDesk`, memakai ulang query dan kartu antrian dari Meja CS.

**Fase 1 — pakai data yang sudah ada:**

1. **Strip KPI** (`report.view`): booking bulan ini, pendapatan kotor, lead baru, tingkat konversi. Diambil dari `DashboardMetricsService`.
2. **Operasional hari ini**
   - Keberangkatan hari ini/besok dan statusnya.
   - **Booking tanpa driver** yang berangkat ≤ 7 hari (booking tanpa `activeAssignment`), dengan tombol ke kalender armada.
   - Booking `confirmed` yang belum dibayar mendekati tanggal berangkat.
3. **Pengawasan tim CS**
   - Tabel per CS: lead aktif, follow-up terlambat, quotation terkirim, booking bulan ini, konversi.
   - **Lead belum ditugaskan** (`assigned_to` kosong), dengan aksi assign.
   - Follow-up terlambat lintas tim.
4. **Kesehatan katalog**
   - Paket dipublikasi tanpa harga (`starting_price_idr` kosong) atau dengan item yang rate-nya habis ≤ 30 hari (`rates.valid_to`).
   - Draf paket yang belum dipublikasi.
5. **Pengecualian**: pembatalan 7 hari terakhir (dari `booking_status_histories`) beserta alasan dan pelakunya.

**Fase 2 — butuh data/alur baru:**
- Waktu respons CS (lead masuk → kontak pertama; perlu stempel `first_contacted_at`).
- Antrian persetujuan: pembatalan atau diskon oleh CS di atas batas tertentu harus disetujui Admin. Refund besar diteruskan ke Finance.
- Target penjualan per CS.

### 4.3 Aksi

- **Assign lead massal**: checkbox di daftar lead + pilih CS (hanya user cabang dengan `lead.manage`, memakai daftar yang sudah difilter di `LeadForm`). Setiap perubahan dicatat di activity log.
- Assign driver: memakai kalender armada yang ada.
- Publish/unpublish paket: toggle cepat di daftar paket (logic sama dengan builder, harga mulai dihitung ulang).
- Kelola user CS cabang sendiri: **tidak di fase 1**. `user.manage` bersifat global, jadi memberikannya ke Admin berarti Admin bisa mengubah user cabang lain dan Super Admin. Opsi yang lebih aman: permission baru `user.manage.branch` (hanya user di cabang sendiri, hanya role CS). Ini **keputusan owner**.

### 4.4 Navigasi

- Login Admin langsung ke **Meja Admin**. `DashboardController` diperluas: jika punya `driver.assign` + `lead.manage` tapi tidak `pricing.manage`, arahkan ke `admin.control`.
- Urutan sidebar: Meja Admin · Meja CS · Lead · Pemesanan · Armada · Inventori · Laporan · Audit log.
- Meja CS tetap bisa dibuka Admin, untuk melihat apa yang dilihat tim CS.

## 5. Tahapan kerja

| # | Pekerjaan | Estimasi |
|---|-----------|----------|
| 1 | Role "Admin Cabang" di seeder + akun demo `admin.bali@indogate.com` + redirect login | 0,25 hari |
| 2 | Ekstrak query antrian Meja CS jadi service bersama | 0,5 hari |
| 3 | Meja Admin: KPI, operasional hari ini, booking tanpa driver | 1 hari |
| 4 | Panel tim CS (tabel per CS, lead belum ditugaskan) | 1 hari |
| 5 | Assign lead massal + activity log | 0,5 hari |
| 6 | Kesehatan katalog + toggle publish cepat | 0,5 hari |
| 7 | Sidebar, i18n (id/en/ar), RTL, QA Chrome sebagai Admin | 0,5 hari |
| 8 | (Fase 2) persetujuan, waktu respons, user per cabang | tergantung keputusan |

Total fase 1 ± **4,25 hari kerja**.

## 6. Test otomatis

- Admin Cabang: login diarahkan ke Meja Admin; tidak bisa membuka pembayaran/verifikasi, pricing engine, user, atau role (403).
- Role Admin Cabang lolos aturan pemisahan tugas. Kalau `payment.verify` ditambahkan di matriks, simpan harus ditolak.
- Assign massal hanya menerima CS di cabang yang sama; lead cabang lain tidak ikut.
- Booking tanpa driver: yang sudah ditugaskan tidak muncul.
- Data cabang lain tidak tampil di semua panel.

## 7. Risiko

- **`user.manage` global.** Jangan diberikan ke Admin sebelum ada versi per cabang.
- **Margin.** Kalau Admin tidak diberi `report.margin.view`, KPI pendapatan tetap tampil tapi kolom modal/margin disembunyikan. Pastikan `DashboardOverview` menghormati `canViewFinancials`.
- **Tumpang tindih dengan Super Admin.** Mr. Ahmed tetap melihat semua. Meja Admin juga bisa dibuka Super Admin untuk pengecekan.
- **Performa** tabel per CS: agregasi per cabang kecil, aman. Beri indeks `leads(branch_id, assigned_to, status)` kalau lead sudah ribuan.

## 8. Keputusan yang dibutuhkan dari owner

1. Apakah Admin boleh melihat **modal & margin** (`report.margin.view`)? Rekomendasi: **ya**, karena dia yang mengawasi profit cabang untuk Mr. Ahmed.
2. Apakah Admin boleh **mengelola akun CS** cabangnya sendiri (butuh `user.manage.branch` baru)? Atau tetap lewat Mr. Ahmed?
3. Apakah pembatalan/diskon oleh CS perlu **persetujuan Admin** (fase 2), atau cukup dipantau di panel pengecualian?

## 9. Naskah demo presentasi (± 5 menit)

1. Login sebagai `admin.bali`. Langsung terbuka Meja Admin: KPI bulan ini, keberangkatan besok, 2 booking belum ada driver.
2. Klik booking tanpa driver, lalu assign driver di kalender armada; booking hilang dari antrian.
3. Panel tim CS: CS Bali punya 3 follow-up terlambat. Pilih 5 lead belum ditugaskan dan assign massal ke CS Bali.
4. Kesehatan katalog: satu paket rate-nya habis bulan depan. Buka dan perbarui, atau unpublish.
5. Tunjukkan batasnya: menu Pembayaran dan Pengaturan harga tidak ada. "Uang diverifikasi Finance, harga diputuskan Mr. Ahmed."
6. Pindah ke akun CS: yang tadi di-assign muncul di Meja CS miliknya.
