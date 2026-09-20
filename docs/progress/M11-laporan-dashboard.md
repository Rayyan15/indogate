# Progress Modul M11 — Laporan & Dashboard

**Status:** Selesai (100% verified via automated tests & real browser)  
**Referensi:** `docs/PRD_Indogate_v3.1.md` §M11

---

## 1. Ruang Lingkup & Kebutuhan Bisnis

Modul M11 menyediakan gambaran operasional dan keuangan yang dapat ditindaklanjuti, baik per cabang maupun gabungan semua cabang. Modul ini mencakup Executive Dashboard terpusat dengan KPI real-time serta Reports Center yang menyajikan laporan penjualan & margin sejati, corong konversi prospek (lead funnel), dan utilisasi operasional/armada.

### Komponen Kunci

1. **Domain Services (`app/Domain/Reporting/Services/`)**:
   - `DashboardMetricsService`:
     - Resolusi rentang tanggal periode (`resolveDateRange`): hari ini, 7 hari terakhir, bulan ini, bulan lalu, kuartal ini, tahun ini, semua waktu, kustom.
     - Ringkasan pemesanan paket (`getBookingsSummary`): total, confirmed, partially_paid, paid, in_progress, completed, cancelled.
     - Metrik keuangan & margin sejati (`getFinancialMetrics`): Gross Revenue, Biaya Kanal (MDR), Net Revenue, Biaya Modal Vendor (HPP), Margin Bersih Sejati & persentase margin.
     - Corong konversi lead (`getLeadFunnelMetrics`): total lead, penawaran (quotation), lead won, lead lost, persentase konversi, rincian status lead, dan breakdown alasan kekalahan/penolakan (`lost_reasons`).
     - Utilisasi operasional (`getOperationalStats`): driver aktif, kendaraan aktif, penugasan armada berjalan, mitra hotel.
     - Riwayat pemesanan terbaru (`getRecentBookings`) dan antrean verifikasi pembayaran (`getPendingVerifications`).
     - Peringkat performa paket tur (`getPackagePerformance`) berdasarkan kontribusi omzet dan margin.
     - Resolusi isolasi cabang aman (`resolveEffectiveBranchId`).
   - `ReportExportService`:
     - Ekspor CSV stream dengan awalan **UTF-8 BOM (`\xEF\xBB\xBF`)** agar langsung kompatibel dengan Microsoft Excel tanpa kendala karakter khusus/aksen internasional.
     - Ekspor Sales & Margin, Lead Conversion, dan Operasional Pemesanan.

2. **Segregation of Duties & Otorisasi RBAC**:
   - `report.view`: Izin akses ke dasbor dan laporan operasional.
   - `report.margin.view`: Izin finansial sensitif. Hanya Super Admin dan Finance Admin yang dapat melihat metrik margin dan tab *Sales Margin*. CS Admin otomatis dialihkan ke tab *Lead Conversion* dan disembunyikan dari angka margin/keuntungan.
   - `branch.switch`: Menentukan apakah pengguna dapat melihat semua cabang / beralih cabang atau dikunci hanya ke cabangnya sendiri.

3. **Komponen Livewire & UI Presentation**:
   - `DashboardOverview` (`/admin/dashboard`):
     - Kartu KPI modern dengan aksen warna brand Indogate (Red #C41230 & Neutral Charcoal/White).
     - Filter periode interaktif dan reaktif (`wire:model.live="period"`).
     - Filter cabang dinamis.
     - Tombol ekspor cepat sesuai hak otorisasi.
     - Daftar antrean verifikasi pembayaran dan pemesanan terbaru.
     - Grafik peringkat margin per paket wisata.
   - `ReportsCenter` (`/admin/reports`):
     - Pusat laporan 3 tab: *Penjualan & Margin*, *Konversi Lead*, dan *Operasional & Armada*.
     - Fitur pencarian terproteksi dan paginasi data.
     - Tombol ekspor CSV per tab.

4. **Internasionalisasi (i18n) & Dukungan RTL**:
   - Dukungan penuh 3 bahasa pada berkas `lang/id/report.php`, `lang/en/report.php`, dan `lang/ar/report.php`.
   - Tata letak otomatis menyesuaikan arah baca kanan-ke-kiri (`dir="rtl"`) saat lokal Arab (`/ar/admin/dashboard`) aktif.

---

## 2. Hasil Pengujian Otomatis (PHPUnit)

Seluruh pengujian Modul M11 dibuat di `tests/Feature/Reporting/` dan lulus 100%:

| Berkas Uji | Kasus yang Diuji | Status |
|---|---|---|
| `DashboardTest.php` | Render dashboard pengguna berwenang, kalkulasi metrik riil Livewire, reaktivitas pergantian periode | Lulus |
| `LeadConversionReportTest.php` | Perhitungan corong konversi prospek, rasio konversi (Won/Total), agregasi alasan kalah | Lulus |
| `BranchFilterTest.php` | Isolasi data cabang (Bali vs Jakarta), akses gabungan Super Admin, filter cabang spesifik | Lulus |
| `ReportPermissionTest.php` | Customer ditolak (403), Finance Admin melihat margin penuh, CS Admin dibatasi dari margin | Lulus |
| `ExportTest.php` | Ekspor CSV ber-BOM UTF-8 valid, verifikasi header/baris, proteksi izin ekspor | Lulus |

Total test suite sistem: **228 passed (619 assertions)**.

---

## 3. Hasil Pengujian Real Browser (Puppeteer / Edge)

Pengujian browser nyata (`test-reporting-browser.js`) pada `http://127.0.0.1:8000`:
- **Test 1 (Super Admin Dashboard)**: Berhasil membuka `/id/admin/dashboard`, memuat seluruh kartu KPI, dan dropdown periode berubah secara reaktif.
- **Test 2 (Pusat Laporan & Navigasi Tab)**: Berhasil membuka `/id/admin/reports`, berpindah ke tab *Konversi Lead* (funnel termuat), lalu berpindah ke tab *Operasional & Armada* (metrik driver/kendaraan termuat).
- **Test 3 (i18n & Tata Letak RTL)**: Berhasil membuka `/ar/admin/dashboard`, atribut `dir="rtl"` terverifikasi aktif, dan seluruh teks KPI berbahasa Arab tampil presisi.
- **Test 4 (Finance Admin Access)**: Login peran Finance Admin memuat angka Pendapatan Kotor dan Margin Bersih Sejati secara lengkap.
- **Test 5 (CS Admin Restricted Access)**: Login peran CS Admin memuat dashboard operasional tanpa membocorkan margin finansial.

Status browser test: **ALL M11 BROWSER TESTS COMPLETED SUCCESSFULLY 100%**.
