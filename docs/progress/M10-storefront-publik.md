# Progress Modul M10 — Storefront Publik

**Status:** Selesai (100% verified via automated tests & real browser)  
**Referensi:** `docs/PRD_Indogate_v3.1.md` §M10

---

## 1. Ruang Lingkup & Kebutuhan Bisnis

Modul M10 menghadirkan storefront publik yang menjadi etalase utama Indogate bagi calon wisatawan mancanegara, khususnya keluarga dari kawasan Timur Tengah (Arab Saudi, UEA, dsb.) dan pelancong global. Halaman dirancang dengan fokus pada **kepercayaan, kemudahan navigasi, dan sensitivitas budaya**, bukan kerumitan fitur.

### Komponen Kunci

1. **Layout Publik Terpisah (`resources/views/layouts/public.blade.php`, `App\View\Components\PublicLayout`)**:
   - Terpisah penuh dari tata letak admin/klien tertutup.
   - Tipografi: Pasangan font **Cairo** untuk bahasa Arab (RTL) dan **Inter** untuk bahasa Indonesia/Inggris (LTR).
   - Skema warna primer konsisten: Aksen merah `#C41230` (`red-600`), abu-abu arang netral `#1A1A19`, serta pembatas radius 4–8px.
   - Aturan desain PRD Bagian 4: Maksimal satu tombol aksi merah primer yang mencolok per layar.
   - Bebas dari emoji di seluruh antarmuka, digantikan ikon SVG stroke profesional.
   - Tombol mengambang (floating CTA) WhatsApp dan header konsultasi langsung.

2. **Landing Page Editorial (`resources/views/public/home.blade.php`)**:
   - **Editorial Hero**: Fotografi pemandangan nusantara beresolusi tinggi dengan kepatuhan penuh terhadap sensitivitas budaya (tanpa alkohol, pakaian sopan/keluarga).
   - **Pilar Kepercayaan Budaya (Cultural Sensitivity & Trust Pillars)**:
     - Jaminan kuliner 100% halal.
     - Jaminan privasi keluarga dan kolam renang vila privat.
     - Opsi pengemudi wanita berlisensi (*female driver option/guarantee*).
     - Pemandu dan staf multibahasa (Arab & Inggris).
   - **Paket Unggulan**: Menampilkan paket pilihan dengan kartu utama berbingkai khusus `border-2 border-red-600` dan kartu pendukung `border-neutral-200`.
   - **Cara Memesan (How It Works)**: 3 langkah ringkas merencanakan liburan privat.
   - **Formulir Konsultasi Cepat (Quick Inquiry Form)**: Dilengkapi proteksi anti-spam honeypot dan pembatasan laju.

3. **Katalog Paket Dinamis (`resources/views/public/catalog.blade.php`, `App\Livewire\Public\PackageCatalog`)**:
   - Rute: `GET /{locale}/packages`.
   - Menampilkan hanya paket dengan status terbit (`is_published = true`).
   - Filter reaktif Livewire:
     - Pencarian teks kata kunci (pada nama dan deskripsi paket lintas bahasa).
     - Filter destinasi / cabang operasional (Bali, Jakarta, dsb.).
     - Filter rentang durasi hari (Singkat 1–3 hari, Sedang 4–7 hari, Panjang 8+ hari).
     - Pengurutan: Terbaru, Harga Terendah, Harga Tertinggi, Durasi Terpendek.
   - Paginasi bersih dan responsif.

4. **Detail Paket & Proteksi Akses (`resources/views/public/package-detail.blade.php`)**:
   - Rute: `GET /{locale}/packages/{package}`.
   - Proteksi otorisasi: Paket berstatus draf atau tidak terbit (`is_published = false`) secara mutlak memicu **HTTP 404 Not Found**.
   - Menampilkan rincian hari demi hari (*day-by-day itinerary*), keunggulan paket (*highlights*), fasilitas termasuk (*inclusions*), dan hal yang tidak termasuk (*exclusions*).
   - Formulir permintaan penawaran paket spesifik terintegrasi langsung dengan CRM Lead.

5. **Multi-Currency Converter Real-Time (`App\Support\Storefront\StorefrontCurrency`)**:
   - Mendukung 3 mata uang utama: **SAR** (Saudi Riyal), **USD** (US Dollar), dan **IDR** (Rupiah).
   - Pemilih mata uang Livewire & formulir POST (`POST /{locale}/currency`) yang dapat mengubah harga seketika tanpa perlu muat ulang halaman.
   - Konversi terhubung dengan modul penetapan harga M4 (`Converter` dan `ExchangeRate`), dengan nilai konversi dasar aman saat tabel nilai tukar belum terisi.

6. **Formulir Permintaan Menjadi Lead & Proteksi Spam**:
   - Rute: `POST /{locale}/leads` (`throttle:10,1`).
   - Setiap formulir valid menghasilkan entri `Lead` baru dengan status `new` dan sumber `LeadSource::WEBSITE`.
   - Bila pemohon menyertakan paket, tanggal perjalanan, jumlah pax, atau catatan khusus, sistem otomatis mencatat `LeadActivity` bertipe `note` pada lead terkait untuk ditindaklanjuti CS Admin.
   - **Proteksi Spam Honeypot**: Field tersembunyi `website` wajib kosong. Bot yang mengisi field ini akan diabaikan tanpa membuat lead.

---

## 2. Struktur Berkas yang Dibuat / Dimodifikasi

| Berkas | Keterangan |
|---|---|
| `database/migrations/2026_09_20_000002_add_storefront_fields_to_packages_table.php` | Menambahkan kolom `is_published`, `is_featured`, `cover_image`, `highlights`, `starting_price_idr` pada tabel packages |
| `app/Domain/Packaging/Models/Package.php` | Model Package dengan scope `published()` dan `featured()`, casts array dan boolean |
| `app/Support/Storefront/StorefrontCurrency.php` | Layanan format & konversi multi-mata uang storefront (SAR, USD, IDR) |
| `app/Http/Controllers/Public/StorefrontController.php` | Controller beranda, katalog, detail paket, dan pengubah mata uang |
| `app/Http/Controllers/Public/LeadCaptureController.php` | Penanganan permintaan prospek publik dengan pencatatan aktivitas paket |
| `app/Livewire/Public/PackageCatalog.php` | Komponen Livewire katalog paket dengan filter reaktif dan mata uang dinamis |
| `app/View/Components/PublicLayout.php` | Komponen layout Blade publik |
| `resources/views/layouts/public.blade.php` | Layout utama publik dengan dukungan font Inter & Cairo, RTL, dan pemilih bahasa/kurs |
| `resources/views/public/home.blade.php` | Halaman utama storefront (hero, pilar budaya, paket unggulan, form) |
| `resources/views/public/catalog.blade.php` | Wrapper halaman katalog paket publik |
| `resources/views/livewire/public/package-catalog.blade.php` | Template Livewire kartu paket dan toolbar filter |
| `resources/views/public/package-detail.blade.php` | Halaman detail paket, itinerary harian, dan formulir booking |
| `lang/{id,en,ar}/storefront.php` | Terjemahan 3 bahasa tersinkronisasi 100% tanpa kunci yang hilang |
| `routes/web.php` | Pendaftaran rute publik storefront dengan fallback pelindung locale |

---

## 3. Hasil Pengujian

### A. Pengujian Otomatis (`php artisan test --filter=Public`)

Semua 20 skenario uji otomatis berjalan sukses 100%:
- `LandingPageTest.php`:
  - `✓ landing page responds with 200 across supported locales`
  - `✓ arabic locale has rtl direction and cairo font`
  - `✓ landing page displays featured packages`
  - `✓ whatsapp consultation button exists`
- `PackageCatalogTest.php`:
  - `✓ catalog shows only published packages`
  - `✓ unpublished package cannot be accessed directly and returns 404`
  - `✓ published package detail returns 200`
  - `✓ catalog livewire filter by search and duration`
- `RequestFormTest.php`:
  - `✓ valid inquiry form submission creates lead`
  - `✓ bot submission with honeypot is silently dropped without creating lead`
  - `✓ invalid submission missing required fields is rejected`
- `RateLimitTest.php`:
  - `✓ repeated lead submissions are rate limited`
- `CurrencySwitchTest.php`:
  - `✓ default currency matches locale`
  - `✓ currency switch updates session`
  - `✓ currency formatter converts idr amount to target currency`

Hasil: **20 passed (84 assertions)**.

### B. Pengujian Nyata di Peramban Web (`test-storefront-browser.js`)

Pengujian dilakukan menggunakan Puppeteer-Core dengan executable Microsoft Edge nyata (`msedge.exe`):
- **Test 1 — Landing Page Multibahasa & RTL**:
  - Halaman Bahasa Indonesia (`/id`): HTTP 200, logo INDOGATE, dan pilar budaya terverifikasi.
  - Halaman Bahasa Arab (`/ar`): HTTP 200, atribut `dir="rtl"`, tipografi Cairo, teks Arab terverifikasi.
  - Tombol WhatsApp floating CTA terverifikasi berfungsi.
- **Test 2 — Katalog Paket & Livewire Currency Switcher**:
  - Halaman katalog (`/en/packages`) menampilkan 3 paket terbit.
  - Pengalihan mata uang ke SAR secara reaktif mengubah format harga ke SAR tanpa reload halaman.
- **Test 3 — Detail Paket & Proteksi Akses**:
  - Akses paket terbit (`/en/packages/1`): HTTP 200, rincian itinerary dan fasilitas tampil lengkap.
  - Akses paket tidak terbit (`/en/packages/4`): HTTP 404 Not Found terverifikasi.
- **Test 4 — Pengiriman Formulir Permintaan Lead**:
  - Input nama, nomor telepon WhatsApp, dan negara asal berhasil disubmit dan menghasilkan pesan sukses terima kasih.
- **Test 5 — Uji Responsivitas Mobile (375x667)**:
  - Dipastikan tidak ada luapan horizontal (`scrollWidth <= clientWidth`).

Hasil: **100% PASSED**.
