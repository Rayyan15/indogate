# Modul M9: Finance & Pembayaran

Status: **Selesai (100% verified via automated tests & real browser)**

---

## Ringkasan Fitur & Arsitektur

Modul M9 mengelola seluruh aspek keuangan, pencatatan dan verifikasi pembayaran, integrasi state machine booking, rekonsiliasi piutang (Accounts Receivable), pembayaran ke vendor, pelaporan laba rugi/margin sejati (true margin deducting MDR & vendor cost), proteksi bukti transfer perbankan, serta penerbitan invoice & kuitansi multibahasa.

### 1. Database & Migrasi
- `database/migrations/2026_09_20_000001_create_finance_tables.php`:
  - Menambahkan foreign key `created_by` pada tabel `package_bookings`.
  - Menggantikan tabel legasi `payment_proofs` dan `payments` dengan tabel arsitektur modern:
    - `payment_intents`: Niat pembayaran (pending, processing, succeeded, failed, cancelled).
    - `payments`: Pembayaran aktual dengan dukungan multi-mata uang (`amount_minor`, `currency`, `fx_rate`, `idr_equivalent_minor`, `channel_fee_minor`, `proof_file`, `channel`, `status`, `created_by`, `verified_by`, `verified_at`, `rejection_reason`).
    - `vendor_payments`: Pencatatan pembayaran pengeluaran ke vendor mitra hotel/transport/penerbangan.
    - `refunds`: Pengembalian dana dengan alasan wajib (`amount_minor`, `currency`, `fx_rate`, `idr_equivalent_minor`, `reason`, `processed_by`, `status`, `refunded_at`).

### 2. Domain Models & Services
- **Models (`app/Domain/Finance/Models/`)**:
  - `Payment`: Relasi ke branch, booking, creator, verifier, refunds.
  - `PaymentIntent`: Penyimpanan parameter intent pembayaran dan channel fee.
  - `VendorPayment`: Relasi ke branch, booking, partner vendor, creator.
  - `Refund`: Relasi ke branch, booking, payment yang direfund, processor.
- **Provider Pattern (`app/Domain/Finance/`)**:
  - `PaymentProviderInterface`: Kontrak standar untuk provider pembayaran (`createIntent`, `verifyPayment`, `processRefund`, `getName`).
  - `ManualTransferProvider`: Implementasi transfer bank manual dengan penghitungan channel fee MDR dan fx_rate.
- **PaymentService (`app/Domain/Finance/Services/PaymentService.php`)**:
  - `recordPayment`: Pencatatan pembayaran dengan konversi kurs SAR/USD ke IDR dan proteksi file bukti.
  - `verifyPayment`: Penegakan RBAC `payment.verify`, proteksi **Self-Approval Protection** (`SelfApprovalException`), dan pemicu rekonsiliasi status booking (`BookingStateMachine`).
  - `rejectPayment`: Penolakan pembayaran dengan alasan wajib dan pencatatan verifier.
  - `refundPayment`: Pengembalian dana dengan validasi nominal dan alasan wajib.
  - `reconcileBookingStatus`: Transisi status otomatis: `confirmed` -> `partially_paid` saat DP diverifikasi, dan `partially_paid` -> `paid` saat lunas.
- **MarginReportService (`app/Domain/Finance/Services/MarginReportService.php`)**:
  - Menghitung margin sejati per pemesanan: Gross Revenue (IDR) - Channel Fees/MDR (IDR) = Net Revenue (IDR) - Vendor Costs (IDR) = Actual Margin (IDR) & Persentase Margin.
  - Menghitung ringkasan agregat seluruh armada/pemesanan.

### 3. Segregation of Duties (SOD) & Self-Approval Guard
- **Segregation of Duties**:
  - CS Admin memiliki `booking.manage`, tidak memiliki `payment.verify`.
  - Finance Admin memiliki `payment.verify`, `report.margin.view`, tidak memiliki `booking.manage`.
  - Di UI dan backend, CS Admin tidak dapat memverifikasi pembayaran.
- **Self-Approval Protection (PRD M9)**:
  - Pembuat pemesanan (`booking->created_by`) dilarang memverifikasi pembayaran pada pemesanannya sendiri (`SelfApprovalException`).
  - Harus diverifikasi oleh staf Finance lain.

### 4. Dokumen & Keamanan Berkas
- `app/Http/Controllers/Admin/FinanceDocumentController.php`:
  - `invoice`: Menghasilkan faktur penagihan 3 bahasa (ID, EN, AR) dengan layout RTL-aware.
  - `receipt`: Menghasilkan kuitansi pembayaran resmi bertanda tangan digital.
  - `downloadProof`: Unduhan bukti transfer yang tersimpan di disk lokal privat (`storage/app/private/payment-proofs/`), wajib menggunakan Signed Route URL berbatas waktu. Akses URL unsigned langsung ditolak HTTP 403.

### 5. UI Livewire Components
- `PaymentList`: Daftar seluruh pembayaran masuk dengan filter status, tanggal, mata uang, modal penolakan, tombol verifikasi, dan tautan kuitansi/bukti.
- `AccountsReceivableList`: Laporan piutang pemesanan aktif, pelacakan sisa tagihan, deteksi jatuh tempo/overdue, dan tautan invoice.
- `VendorPaymentList`: Manajemen pencatatan pembayaran ke mitra vendor (hotel, maskapai, transport).
- `MarginReport`: Dasbor laporan profitabilitas pemesanan dengan kartu metrik ringkasan (Gross, MDR, Vendor Cost, Net Margin).
- `PackageBookingShow`: Kartu Keuangan & Pembayaran terpadu dengan riwayat pembayaran, aksi Catat Pembayaran, aksi Verifikasi (khusus Finance), aksi Refund berizin, dan tombol Faktur/Kuitansi.

---

## Verifikasi Pengujian

### 1. Automated Tests (Unit, Feature & Stress Testing)
Semua 34 pengujian modul M9 lulus 100% (dari total 217 tests / 566 assertions di seluruh aplikasi):
- `Tests\Unit\Finance\PaymentProviderTest`: Provider contract, pending intent, non-positive amount rejection, verification timestamp, refund processing, empty reason validation (6 tests).
- `Tests\Feature\Finance\PaymentVerificationTest`: Finance admin verification, CS Admin segregation of duties rejection, multi-step full payment transition to `paid`, payment rejection with reason (4 tests).
- `Tests\Feature\Finance\SelfApprovalTest`: Creator cannot verify payment on own booking (`SelfApprovalException`), different Finance Admin can verify (2 tests).
- `Tests\Feature\Finance\ReconciliationTest`: Stepped payments, pending/rejected exclusion, refund adjustment on remaining balance (2 tests).
- `Tests\Feature\Finance\MultiCurrencyPaymentTest`: SAR payment with exchange rate, custom fx_rate override, IDR unit rate (3 tests).
- `Tests\Feature\Finance\MarginReportTest`: MDR deduction, vendor costs deduction, net margin, multi-booking summary aggregation (2 tests).
- `Tests\Feature\Finance\RefundTest`: Empty reason rejection, unverified payment rejection, CS Admin rejection, Finance Admin success (4 tests).
- `Tests\Feature\Finance\FinanceProofAccessTest`: Unsigned URL blocked 403, signed URL download allowed 200, invoice and receipt document rendering (3 tests).
- `Tests\Feature\Finance\FinanceComprehensiveStressTest`: Pengujian batas dan ketahanan logika komprehensif (8 tests / 38 assertions):
  1. Penolakan nominal pembayaran negatif dan nol.
  2. Penolakan pencatatan pembayaran pada pemesanan yang dibatalkan (`cancelled`).
  3. Idempotensi verifikasi ganda (pembayaran yang sudah verified tidak memicu efek ganda).
  4. Pencegahan over-refund kumulatif dari beberapa refund bertahap.
  5. Isolasi tenant/cabang pada pemilihan vendor pembayaran mitra.
  6. Isolasi tenant/cabang pada pencatatan pembayaran pemesanan.
  7. Proteksi wildcard SQL injection (`%`, `_`) pada fitur pencarian Livewire.
  8. Ketepatan kalkulasi Margin Report ketika biaya vendor melebihi pendapatan (margin negatif/rugi).

Total suite seluruh aplikasi: **217 tests passed (566 assertions)**.

### 2. Comprehensive Real Browser Testing (Puppeteer / Edge-Chromium)
Diuji langsung pada server lokal `http://127.0.0.1:8000` mencakup 10 skenario end-to-end:
- **Test 1 (CS Admin Down Payment & SOD)**: Login sebagai CS Bali (`id=2`), membuka detail booking, mencatat DP Rp 2.000.000 via transfer BCA. Verifikasi SOD: tombol "Verifikasi" tidak muncul untuk peran CS.
- **Test 2 (Finance Admin Verifikasi DP)**: Login sebagai Finance Bali (`id=3`), membuka `/id/admin/finance/payments`, mengklik tombol "Verifikasi" pada pembayaran pending.
- **Test 3 (State Transition partially_paid)**: Membuka detail booking, memastikan status booking otomatis bertransisi menjadi `partially_paid` (label UI: "DP Terbayar").
- **Test 4 (Pelunasan Penuh & State Transition paid)**: Finance Admin mencatat sisa tagihan (Rp 737.000.000) dan langsung memverifikasinya. Status pemesanan otomatis bertransisi menjadi `paid` (label UI: "Lunas").
- **Test 5 (Refund Bertahap)**: Finance Admin memproses refund Rp 500.000 dengan alasan pembatalan tur opsional. Berhasil dicatat dan dihitung mengurangi saldo terbayar.
- **Test 6 (Vendor Payment CRUD)**: Membuka `/id/admin/finance/vendor-payments`, mencatat pembayaran vendor Rp 3.500.000 untuk hotel mitra, data muncul di tabel, lalu menguji penghapusan (delete) pembayaran vendor dengan konfirmasi.
- **Test 7 (Pencarian & Filter Pembayaran Masuk)**: Mencari pembayaran berdasarkan kode pemesanan pada `/id/admin/finance/payments`, daftar otomatis memfilter data yang relevan.
- **Test 8 (Daftar Piutang / Accounts Receivable)**: Membuka `/id/admin/finance/receivables`, seluruh kartu ringkasan piutang dan tabel piutang pemesanan termuat dengan benar.
- **Test 9 (Laporan Margin Sesungguhnya)**: Membuka `/id/admin/finance/margin-report`, metrik agregat PENDAPATAN KOTOR, BIAYA KANAL (MDR), BIAYA MODAL VENDOR (HPP), dan MARGIN SESUNGGUHNYA terhitung presisi dari data riil.
- **Test 10 (Faktur & Kuitansi Multibahasa)**: Membuka `/id/admin/finance/invoices/2` dan kuitansi, dokumen tercetak lengkap dengan nomor referensi resmi dan rincian transaksi.

### 3. Code Style & Standar
- `vendor/bin/pint`: Passed (100% PSR-12 / Laravel standards).
- `php artisan lang:missing`: Passed (0 missing keys across `id`, `en`, `ar`).

