# 07. Laporan Temuan Potensi Bug dan Risiko Sistem — Indogate

Dokumen ini berisi hasil audit menyeluruh (*swarm bug hunting*) terhadap seluruh lapisan aplikasi: Backend Domain, CRUD & Multi-Tenant, Frontend & Reaktivitas Livewire, Keamanan & Otorisasi, serta CI/CD dan Kompatibilitas Basis Data.

---

## 1. Tingkat Kritis / Prioritas Tinggi (High Impact)

### 1.1 Bug Kalkulasi Finansial: Multiplikasi 100x Nilai Tukar Multi-Currency
- **Lokasi:**
  - `app/Domain/Finance/Services/PaymentService.php` (Baris 57)
  - `app/Domain/Finance/Providers/ManualTransferProvider.php` (Baris 87)
  - `app/Livewire/Admin/Finance/VendorPaymentList.php` (Baris 112)
- **Kode:**
  ```php
  $idrEquivalentMinor = $currency === 'IDR' ? $amountMinor : (int) round($amountMinor * $fxRate);
  ```
- **Penyebab & Dampak:**
  Pada mata uang USD dan SAR, `$amountMinor` disimpan dalam satuan sen/halala (1 USD = 100 cent, 1 SAR = 100 halala). Sedangkan IDR tidak memiliki angka pecahan (1 minor = 1 IDR). Nilai `$fxRate` adalah rasio IDR per 1 unit *major* (misal 1 USD = 16.000 IDR).
  Akibatnya, pembayaran 1.000 USD (`amountMinor = 100.000`) dihitung menjadi: `100.000 * 16.000 = 1.600.000.000 IDR`, padahal nilai aslinya adalah **16.000.000 IDR**. Seluruh pencatatan ekuivalen IDR untuk mata uang asing tercatat **100 KALI LIPAT LEBIH BESAR**.
- **Solusi:** Bagi `$amountMinor` dengan 100 (atau skala desimal mata uang) sebelum dikalikan `$fxRate`, atau kalikan `$fxRate` dengan nilai mayor `bcdiv($amountMinor, 100, 2)`.

---

### 1.2 Kerusakan Fatal MySQL 8.0+: Query `LIKE` pada Kolom JSON Translatable
- **Lokasi:**
  - `app/Livewire/Admin/Packaging/PackageList.php` (Baris 58)
  - `app/Livewire/Admin/Catalog/InventoryItemList.php` (Baris 51)
  - `app/Livewire/Admin/Catalog/PartnerList.php` (Baris 46)
  - `app/Livewire/Admin/Packaging/PackageBuilder.php` (Baris 115)
  - `app/Livewire/Public/PackageCatalog.php` (Baris 82–83)
- **Penyebab & Dampak:**
  Kolom `name` dan `description` disimpan dalam tipe data native `$table->json()`. SQLite memperlakukan JSON sebagai teks biasa sehingga `where('name', 'like', ...)` berjalan lancar di unit test. Namun pada MySQL 8.0+ produksi, query `LIKE` pada tipe JSON adalah operasi ilegal dan langsung melempar exception:
  `ERROR 3146 (22018): Invalid data type for JSON data in expression to function 'like'`.
  Fitur pencarian di kelima modul tersebut akan crash HTTP 500 seketika di MySQL produksi.
- **Solusi:** Gunakan query ekstraksi JSON Laravel: `whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%{$escaped}%"])` atau gunakan query terjemahan Spatie: `where('name->en', 'like', ...)` / `where('name->id', 'like', ...)`.

---

### 1.3 Kebocoran Data Lintas Cabang via Manipulasi Query String `$branchId`
- **Lokasi:**
  - `app/Livewire/Admin/Reporting/ReportsCenter.php` (Baris 28, 50, 72, 88–92, 153, 173, 190)
  - `app/Livewire/Admin/Dashboard/DashboardOverview.php` (Baris 18, 36, 56, 70, 123)
- **Penyebab & Dampak:**
  Properti `public ?int $branchId = null;` didaftarkan ke `$queryString` tanpa proteksi `#[Locked]`. Method `updatingBranchId()` tidak memverifikasi izin `branch.switch`.
  Query di kedua komponen memanggil `$query->withoutGlobalScopes()->where('branch_id', $this->branchId)`.
  CS Admin atau staf cabang biasa cukup menambahkan `?branchId=2` atau mengosongkannya `?branchId=` untuk melihat dan mengekspor seluruh laporan margin, omzet, lead, dan penugasan armada cabang lain atau seluruh cabang sekaligus.
- **Solusi:** Kunci `$branchId` dan paksa nilai `$this->branchId = CurrentBranch::id()` di `boot()` jika pengguna tidak memiliki izin `branch.switch`.

---

### 1.4 Kehilangan Data Paket Wisata pada Alpine/Livewire Sync di `PackageBuilder`
- **Lokasi:**
  - `resources/views/livewire/admin/packaging/package-builder.blade.php` (Baris 107, 187–210)
  - `app/Livewire/Admin/Packaging/PackageBuilder.php` (Baris 186–196)
- **Penyebab & Dampak:**
  Daftar item paket (`items`) dimanipulasi di sisi klien oleh Alpine.js. Data Alpine hanya disinkronkan ke server Livewire saat fungsi `recalculate()` dipanggil.
  Tombol simpan utama hanya mengeksekusi `wire:click="save"`. Jika admin menambah/mengubah komponen dan langsung mengklik tombol "Simpan" tanpa mengklik "Hitung Ulang" terlebih dahulu, `$this->items` di server kosong/basi. Server menjalankan `$package->items()->delete()` lalu menyimpan 0 item. Seluruh itinerari yang disusun pengguna terhapus total.
- **Solusi:** Sinkronkan state Alpine ke Livewire sebelum save dieksekusi: `x-on:click="$wire.call('recalculate', items, $wire.current_pax).then(() => $wire.save())"`.

---

### 1.5 Kerentanan Manipulasi Harga Belanja Pelanggan (*Price Tampering*)
- **Lokasi:**
  - `app/Http/Controllers/Customer/CartController.php` (Baris 17–32)
  - `app/Http/Controllers/Customer/CheckoutController.php` (Baris 23–25, 51–68)
- **Penyebab & Dampak:**
  Pada endpoint `POST /cart/add`, parameter `price` diambil mentah dari request pengguna dan disimpan ke sesi. Saat checkout, `CheckoutController` langsung menggunakan nilai `price` dari sesi tanpa mencocokkan ulang ke harga asli di database/PricingEngine. Pengguna dapat mengubah payload request menjadi Rp1 dan berhasil checkout.
- **Solusi:** Hapus input `price` dari request client; kalkulasi harga murni di server berdasarkan `bookable_type` dan `bookable_id`.

---

### 1.6 Penjumlahan Multi-Currency Campur Aduk pada Booking
- **Lokasi:** `app/Domain/Booking/Models/PackageBooking.php` (Baris 103–114)
- **Kode:**
  ```php
  public function totalPaidMinor(): int {
      return (int) $this->payments()->where('status', 'verified')->sum('amount_minor') - ...;
  }
  ```
- **Penyebab & Dampak:**
  Jika booking berdenominasi IDR dan pembayaran dilakukan dalam USD (`amount_minor = 320.000`), nilai 320.000 langsung dijumlahkan mentah ke mata uang IDR (sehingga terhitung baru bayar Rp3.200). Sebaliknya jika booking dalam USD dan tamu transfer IDR, booking langsung dianggap lunas berlebih.
- **Solusi:** Seluruh perhitungan sisa piutang dan pembayaran wajib distandarisasi ke mata uang booking atau ekuivalen IDR.

---

### 1.7 Kerusakan Alur Pembayaran Warisan Akibat Drop Tabel Migrasi
- **Lokasi:**
  - `database/migrations/2026_09_20_000001_create_finance_tables.php` (Baris 19–24)
  - `app/Http/Controllers/Admin/PaymentController.php`
  - `app/Http/Controllers/Customer/DashboardController.php` (Baris 30, 46–58)
  - `routes/web.php` (Baris 91–100, 187)
- **Penyebab & Dampak:**
  Migrasi `2026_09_20_000001` menghapus tabel `payment_proofs` dan merombak tabel `payments`. Namun controller lama `PaymentController` dan `Customer\DashboardController` beserta rutenya masih aktif. Mengakses halaman detail pembayaran lama langsung melempar error: `Table 'payment_proofs' doesn't exist`.
- **Solusi:** Cabut rute dan controller legacy yang sudah digantikan penuh oleh modul modern M9 (`PaymentList` dan `PackageBookingShow`).

---

## 2. Tingkat Sedang (Medium Impact)

### 2.1 Celah Otorisasi Metode Publik Livewire (`/livewire/update`)
Karena request `/livewire/update` tidak melewati middleware `role:` atau `permission:`, dan tidak menjalankan `mount()` pada subsequent update, metode `save()` pada komponen berikut dapat dieksekusi tanpa validasi izin:
- `DriverList::save()`: Pembuatan driver baru tidak memanggil `$this->authorize('create', Driver::class)`.
- `VehicleList::save()`: Pembuatan kendaraan baru tidak memanggil `$this->authorize('create', Vehicle::class)`.
- `VendorPaymentList::save()`: Pembuatan pembayaran vendor tidak memanggil `$this->authorize('create', VendorPayment::class)`.
- `LeadForm::save()`: Pembuatan lead baru tidak memiliki otorisasi create.
- `CreateQuotation::convertToBooking()`: Tidak memvalidasi izin `booking.manage`.

### 2.2 Validasi `exists` Tanpa Filter `branch_id`
- `BookingController::assignDriver`: `exists:drivers,id` tanpa scope branch.
- `InventoryItemManager`: `partner_id` validasi `exists:partners,id` tanpa scope branch.
- `CreateQuotation`: `package_id` validasi `exists:packages,id` tanpa scope branch.
- `LeadForm`: `assigned_to` dropdown dan validasi memuat user seluruh cabang dan user nonaktif.

### 2.3 SQL Wildcard Injeksi & Full Table Scan pada Pencarian Teks
Input pencarian `$this->search` di-concat langsung ke `"%{$this->search}%"` tanpa `addcslashes($this->search, '%_\\')` pada 11 komponen Livewire (`PackageBookingList`, `InventoryItemList`, `PartnerList`, `MarginReport`, `AssignmentCalendar`, `DriverList`, `VehicleList`, `LeadList`, `PackageBuilder`, `PackageList`, `UserList`). Jika pengguna mengetik `%` atau `_`, database melakukan full scan tak terduga.

### 2.4 Precedence Operator SQL pada `orWhere` Tanpa Pembungkus
Pada `ReportsCenter.php` (baris 176–180) dan `UserList.php` (baris 44–46), klausa `orWhere` tidak dibungkus dalam nested closure:
`WHERE created_at >= ? AND created_at <= ? AND name LIKE ? OR phone LIKE ?`.
Kondisi ini membocorkan baris data tanpa memedulikan filter tanggal dan cabang.

### 2.5 Silent Validation Failures pada Antarmuka Pengguna
- `package-builder.blade.php`: Error pada `name.id` dan `name.ar` tidak pernah ditampilkan karena view hanya mengikat `:error="$errors->first('name.en')"`.
- `package-booking-show.blade.php`: Form penugasan supir tidak menampilkan error tanggal (`assignment_date_from`, `assignment_date_to`), dan modal pembayaran tidak menampilkan error file bukti atau kurs.
- `partner-form.blade.php` dan `vendor-payment-list.blade.php`: Input kota, kontak, tanggal bayar, dan kurs tidak memiliki binding pesan galat.

### 2.6 Ketiadaan Proteksi Double-Submission (`wire:loading.attr="disabled"`)
Tombol aksi penting seperti verifikasi pembayaran (`verifyBookingPayment`), penugasan supir (`assignDriver`), refund (`processRefund`), dan konversi booking (`convertToBooking`) tidak menonaktifkan tombol saat loading, memungkinkan multi-klik yang memicu penulisan data ganda.

### 2.7 Ketiadaan `wire:key` pada Looping Livewire
Looping `@foreach` dan `@forelse` pada 11 komponen (termasuk katalog paket, baris pembayaran, kalender armada, dan audit log) tidak memiliki atribut `wire:key`, memicu error diffing Morphdom saat DOM diperbarui.

---

## 3. Tingkat Rendah / Peningkatan Arsitektural (Low Impact)

### 3.1 Properti Model yang Tidak Ada di Laporan Operasional
Pada `reports-center.blade.php` (baris 336–337), kode memanggil `$assign->vehicle?->license_plate` dan `$assign->vehicle?->model`. Model `Vehicle` hanya memiliki properti `plate` dan `type`. Akibatnya kolom armada di laporan operasional selalu tampil `- (-)`.

### 3.2 Teks Hardcode Bahasa Indonesia
Sebagian besar teks antarmuka di `package-booking-show`, `vendor-payment-list`, `accounts-receivable-list`, dan `assignment-calendar` masih berstatus teks hardcode bahasa Indonesia, belum memanfaatkan translasi `__()` untuk Bahasa Inggris dan Arab.

### 3.3 Penanganan Infinite Polling pada Kegagalan Antrean PDF
Jika job `GeneratePackageItineraryPdf` atau `GenerateBookingVoucher` crash atau kehabisan memori, job tidak mencatat status gagal ke Cache. Akibatnya polling `wire:poll` pada komponen Livewire berputar selamanya tanpa batas waktu.

### 3.4 Sesi Tidak Diregenerasi pada Registrasi Akun
Pada `RegisteredUserController::store()`, pemanggilan `session()->regenerate()` terlewat setelah `Auth::login($user)`.

---

## 4. Status Resolusi & Audit Pasca-Perbaikan (Post-Fix Swarm Verification)

Seluruh perbaikan telah diimplementasikan, diaudit ulang oleh swarm agent multi-perspektif, dan diverifikasi melalui unit/feature testing otomatis (`269 passed`, `821 assertions`).

### 4.1 Ringkasan Status Resolusi Bug Utama

| ID | Modul / Masalah | Status | File Utama yang Diperbaiki |
|---|---|---|---|
| **1.1** | Multiplikasi 100x Valas (USD/SAR) | **RESOLVED** | `PaymentService.php`, `ManualTransferProvider.php`, `VendorPaymentList.php` |
| **1.2** | Crash Fatal MySQL 8.0+ pada JSON LIKE | **RESOLVED** | `PackageList.php`, `InventoryItemList.php`, `PartnerList.php`, `PackageBuilder.php`, `PackageCatalog.php` |
| **1.3** | Kebocoran Data Lintas Cabang via URL Query String | **RESOLVED** | `ReportsCenter.php`, `DashboardOverview.php` |
| **1.4** | Kehilangan Data Itinerari Paket di Alpine Sync | **RESOLVED** | `package-builder.blade.php`, `PackageBuilder.php` |
| **1.5** | Price Tampering pada Belanja Pelanggan | **RESOLVED** | `CartController.php`, `CheckoutController.php`, `CartCheckoutPriceTamperingTest.php` |
| **1.6** | Penjumlahan Multi-Currency Campur Aduk | **RESOLVED** | `PackageBooking.php` (`totalPaidMinor()`), `MultiCurrencyPaymentTest.php` |
| **1.7** | Error Rute Warisan Drop `payment_proofs` | **RESOLVED** | `PaymentController.php`, `Customer\DashboardController.php`, `admin/payments/show.blade.php` |
| **2.1** | Otorisasi Mutasi Livewire (`/livewire/update`) | **RESOLVED** | `DriverList.php`, `VehicleList.php`, `VendorPaymentList.php`, `LeadForm.php`, `CreateQuotation.php`, `PackageBookingPolicy.php` |
| **2.2** | Validasi `exists` Tanpa Scope `branch_id` | **RESOLVED** | `BookingController.php`, `InventoryItemManager.php`, `CreateQuotation.php`, `LeadForm.php` |
| **2.3** | SQL Wildcard Escaping (`%_\\`) | **RESOLVED** | 11 Komponen Livewire via `addcslashes($search, '%_\\')` |
| **2.4** | Operator Precedence `orWhere` | **RESOLVED** | `ReportsCenter.php`, `UserList.php` (dibungkus dalam nested closure) |
| **2.5** | Silent Validation Failures | **RESOLVED** | `package-builder.blade.php`, `package-booking-show.blade.php`, `partner-form.blade.php`, `vendor-payment-list.blade.php` |
| **2.6** | Double Submission & `wire:target` Isolation | **RESOLVED** | Seluruh tombol aksi finansial, penugasan armada, dan switcher mata uang dilengkapi `wire:loading.attr="disabled"` dan `wire:target` spesifik |
| **2.7** | Ketiadaan `wire:key` pada Looping | **RESOLVED** | Seluruh perulangan `@foreach` dan `@forelse` di 11 view blade Livewire |
| **3.1** | Properti Model Salah di Laporan Operasional | **RESOLVED** | `reports-center.blade.php` (`$assign->vehicle?->plate`, `$assign->vehicle?->type`) |

---

### 4.2 Temuan Khusus Post-Fix Swarm Audit & Solusi Lanjutannya

Setelah perbaikan awal dilakukan, pengujian mendalam dengan swarm agent mendeteksi 4 potensi risiko lanjutan yang langsung diperbaiki:

1. **Bug Super Admin "Semua Cabang" & Pencabutan Soft Delete:**
   - *Masalah:* Query `->when($effectiveBranchId, fn ($q) => $q->withoutGlobalScopes()->where('branch_id', $effectiveBranchId))` tidak terpanggil ketika Super Admin memilih "Semua Cabang" (`$effectiveBranchId = null`), sehingga `BranchScope` tetap aktif membatasi data ke cabang Bali. Selain itu, pemanggilan plural `withoutGlobalScopes()` mencabut `SoftDeletingScope`.
   - *Solusi:* Diubah menjadi `->withoutGlobalScope(BranchScope::class)->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))` pada `ReportsCenter.php`, `DashboardOverview.php`, dan `DashboardMetricsService.php`.
   - *Perbaikan Summary Paginasi:* Pemanggilan `$allBookings = (clone $bookingsQuery)->get()` dipindahkan ke **sebelum** `$bookingsQuery->paginate(15)` agar KPI summary menghitung seluruh baris periode, bukan hanya 15 baris halaman aktif.

2. **Kalkulasi Valas Balik & Pencegahan N+1 Query pada Booking Non-IDR:**
   - *Masalah:* Jika booking menggunakan SAR dan pembayaran dalam IDR, membagi nilai IDR dengan `$payment->fx_rate` (yang bernilai 1.0) menyebabkan saldo bayar dihitung 4.250x lipat lebih besar. Pemanggilan `payments()->get()` berulang kali juga memicu query N+1.
   - *Solusi:* Menggunakan kurs konversi booking currency terhadap IDR: `($paymentIdr / $bookingRate) * (10 ** $bookingDecimals)` dan memanfaatkan koleksi yang sudah di-load (`$this->relationLoaded('payments')`).

3. **Graceful Handling Item Keranjang yang Dihapus / Kadaluarsa:**
   - *Masalah:* Jika item dalam keranjang belanja dihapus/di-soft-delete dari database, membuka `/checkout` melempar `ModelNotFoundException` (404) tanpa membersihkan sesi, mengunci pelanggan dalam error 404.
   - *Solusi:* Dibuat helper `resolveAndSanitizeCart()` di `CheckoutController` yang menangani `ModelNotFoundException` secara aman, mengeliminasi item kadaluarsa dari session cart, dan menampilkan flash message peringatan dalam Bahasa Indonesia.

4. **Isolasi Reaktivitas Livewire (`wire:target`) & Inline Preview Bukti Bayar:**
   - *Masalah:* `wire:loading.attr="disabled"` tanpa `wire:target` membekukan tombol-tombol lain ketika input live (datepicker/search) berubah. Penggunaan `Storage::download()` pada `PaymentController::downloadProof` memicu download attachment bukannya menampilkan preview di browser.
   - *Solusi:* Ditambahkan `wire:target` eksplisit pada tombol aksi (`assignFleet`, `verifyBookingPayment`, `openRefundModal`, `recordPayment`, `processRefund`, `setCurrency`), dan `downloadProof` dialihkan ke `Storage::response()` inline dengan pencatatan audit log.

