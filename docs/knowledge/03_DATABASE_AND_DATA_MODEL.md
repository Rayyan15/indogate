# 03. Basis Data dan Model Data — Indogate

---

## 1. Peta Tabel Basis Data Berdasarkan Domain

Aplikasi memiliki total 38 tabel yang dikelompokkan ke dalam domain arsitektur berikut:

```
+-----------------------------------------------------------------------------------+
|                               INDOGATE DATABASE                                   |
+-----------------------------------------------------------------------------------+
|  System & Core    : branches, users, sessions, cache, jobs, passkeys, spatie-rbac |
|  Catalog          : partners, inventory_items, rates, item_media, blackout_dates  |
|  Pricing Engine   : currencies, exchange_rates, seasons, margin_rules, costs      |
|  Packaging        : packages, package_items, package_days                         |
|  Lead & CRM       : leads, lead_activities, quotations, quotation_items           |
|  Booking Domain   : package_bookings, booking_guests, booking_notes, histories    |
|  Fleet Management : drivers, vehicles, driver_assignments                         |
|  Finance Modern   : payment_intents, payments, vendor_payments, refunds           |
|  Legacy / MVP     : customers, hotels, hotel_rooms, flight_routes, bookings, ...  |
+-----------------------------------------------------------------------------------+
```

---

## 2. Pola Desain Kunci pada Basis Data

### 2.1 Presisi Finansial Tanpa Angka Mengambang (Zero-Float Policy)
- Semua nominal uang disimpan dalam satuan terkecil (*minor units*) menggunakan tipe data integer besar (`BIGINT`):
  - `cost_minor`, `unit_price_minor`, `total_minor`, `amount_minor`, `idr_equivalent_minor`, `channel_fee_minor`, `flat_fee_minor`.
  - Contoh: Rp 850.000 disimpan sebagai `85000000` (sen).
- Semua persentase margin dan biaya *gateway* disimpan dalam bentuk bilangan bulat *basis points* (`margin_percent`, `percent_fee`):
  - `100` bps = `1.00%`, `550` bps = `5.50%`, `3500` bps = `35.00%`.
- Kurs valuta asing disimpan dengan presisi tinggi: `DECIMAL(20, 8)` atau `DECIMAL(16, 8)`.

### 2.2 Isolasi Cabang (Multi-Tenancy)
- Kolom `branch_id` (`foreignId('branch_id')->constrained('branches')`) disematkan pada seluruh tabel transaksional.
- Didukung oleh trait `BelongsToBranch`:
  - Menginjeksi otomatis `branch_id = CurrentBranch::id()` saat record baru disimpan.
  - Membatasi pembacaan data melalui `BranchScope`.

### 2.3 Riwayat Kurs Bersifat Insert-Only (Append-Only)
- Tabel `exchange_rates` tidak pernah di-update atau di-delete. Setiap perubahan kurs valuta asing dicatat sebagai baris baru (`insert-only`) dengan timestamp `effective_from` dan referensi user pembuat (`created_by`).
- Hal ini menjamin integritas forensik keuangan dan penelusuran audit nilai tukar historis.

### 2.4 Kepatuhan UU PDP No. 27/2022 (Enkripsi PII)
- Kolom `passport_number` pada tabel `booking_guests` dan `customers` disimpan dalam bentuk terenkripsi di level basis data (`$casts['passport_number'] = 'encrypted'`) menggunakan algoritma AES-256-CBC.
- Berkas paspor disimpan di direktori privat lokal (`storage/app/private/`) dan hanya dapat diakses melalui URL sementara bertanda tangan (*temporary signed URL*).

### 2.5 Dukungan Multibahasa Terintegrasi (JSON Translatable)
- Menggunakan `spatie/laravel-translatable` untuk menyimpan data multibahasa dalam satu kolom JSON (`{"en": "...", "id": "...", "ar": "..."}`):
  - `Partner`: `name`
  - `InventoryItem`: `name`, `description`
  - `Package`: `name`, `description`
  - `PackageDay`: `title`, `notes`
  - `Hotel` (legacy): `name`, `description`

---

## 3. Relasi Antar-Entitas Utama

### 3.1 Katalog & Inventori
- `Partner` (1) ---> (N) `InventoryItem`
- `InventoryItem` (1) ---> (N) `Rate` (jadwal harga berbatas tanggal)
- `InventoryItem` (1) ---> (N) `ItemMedia` (galeri foto)
- `InventoryItem` (1) ---> (N) `BlackoutDate` (jadwal pemeliharaan)

### 3.2 Paket Tur Dinamis
- `Package` (1) ---> (N) `PackageItem` (alokasi komponen inventori harian)
- `Package` (1) ---> (N) `PackageDay` (rencana itinerari harian)
- `PackageItem` (N) ---> (1) `InventoryItem`

### 3.3 Penjualan & Pemesanan
- `Lead` (1) ---> (N) `LeadActivity` (catatan CRM)
- `Lead` (1) ---> (N) `Quotation`
- `Quotation` (1) ---> (N) `QuotationItem`
- `Quotation` (1) ---> (1) `PackageBooking` (dikonversi via `ConvertQuotationToBooking`)
- `PackageBooking` (1) ---> (N) `BookingGuest` (manifest tamu dengan nomor paspor terenkripsi)
- `PackageBooking` (1) ---> (N) `BookingNote` (catatan operasional internal)
- `PackageBooking` (1) ---> (N) `BookingStatusHistory` (audit trail transisi status)

### 3.4 Armada & Supir
- `DriverAssignment` (N) ---> (1) `PackageBooking`
- `DriverAssignment` (N) ---> (1) `Driver`
- `DriverAssignment` (N) ---> (1) `Vehicle`

### 3.5 Finansial
- `PackageBooking` (1) ---> (N) `PaymentIntent`
- `PaymentIntent` (1) ---> (N) `Payment`
- `Payment` (1) ---> (N) `Refund`
- `PackageBooking` (1) ---> (N) `VendorPayment` (pembayaran ke pihak vendor/partner)

---

## 4. Pipeline Database Seeders

### 4.1 Seeder Utama (`DatabaseSeeder`)
1. **`BranchSeeder`**: Mendaftarkan cabang `BALI` (WITA) dan `JKT` (WIB).
2. **`RolesAndPermissionsSeeder`**: Mendaftarkan 12 izin, 4 peran, dan akun default:
   - `admin@indogate.com` (Super Admin, password: "password")
   - `cs.bali@indogate.com` (CS Admin)
   - `finance.bali@indogate.com` (Finance Admin)
   - `customer@indogate.com` (Customer)
3. **`CatalogSeeder`**: Data awal partner (hotel & vendor transportasi), item kamar & armada Hiace, serta tarif dasar untuk Bali dan Jakarta.
4. **`PricingSeeder`**: Mata uang (IDR, USD, SAR), kurs awal, biaya gateway, musim liburan, dan aturan persentase margin per jenis produk.

### 4.2 Seeder Tambahan / Demo
- **`RoleDemoSeeder`**: Akun demonstrasi untuk cabang Jakarta (`cs.jkt@indogate.com`, `finance.jkt@indogate.com`), pengujian user nonaktif, dan customer tambahan.
- **`CatalogDemoSeeder`**: Variasi katalog villa, mobil mewah (Alphard), aktivitas bahari, dan blackout dates.
- **`PricingDemoSeeder`**: Mata uang EUR, riwayat multi-kurs, serta biaya kanal pembayaran khusus.
