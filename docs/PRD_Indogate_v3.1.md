# PRD — Indogate Travel Platform

**Versi:** 3.1 · **Stack:** Laravel 12 + Livewire 4 · **Status:** Draft teknis, menunggu discovery
**Klien:** PT Indo International Gate · **Vendor:** Raynad Digital

| Field | Isi |
|---|---|
| Menggantikan | v3.0 (docx) |
| Perubahan v3.1 | Menambahkan Bagian 4: Design System & Color Palette |
| Disusun oleh | `[NAMA]` |
| Tanggal | `[TANGGAL]` |
| Disetujui | `[NAMA PIC INDOGATE]` |

---

## Cara membaca dokumen ini

Dokumen ini adalah panduan kerja, bukan daftar keinginan. Setiap modul punya empat bagian tetap:

1. **Ruang lingkup** — fitur dan prioritas
2. **Langkah implementasi** — berurutan, siap dikerjakan
3. **Pengujian** — otomatis dan manual, dengan hasil yang diharapkan
4. **Definition of Done** — checklist; modul belum selesai sampai penuh

> **Aturan:** modul dinyatakan selesai hanya jika seluruh DoD tercentang, seluruh uji otomatis lulus, dan uji manual dijalankan **oleh orang lain**, bukan penulis kodenya.

---

# 1. Masalah yang Diselesaikan

| Kode | Masalah | Dampak |
|---|---|---|
| P1 | Harga dikelola manual di spreadsheet | Salah kutip, margin bocor, update lambat |
| P2 | Paket custom disusun manual | Satu penawaran memakan 1–2 jam |
| P3 | Lead lewat WhatsApp tanpa pencatatan | Lead hilang tanpa jejak |
| P4 | Penugasan driver lewat telepon | Jadwal bentrok, preferensi terlewat |
| P5 | Tiga mata uang tanpa kurs terkunci | Selisih kurs menggerus margin |
| P6 | Uang masuk tidak terekonsiliasi dengan booking | Tidak tahu siapa belum lunas |
| P7 | Dokumen identitas di chat pribadi | Eksposur UU PDP |
| P8 | Biaya pembayaran lintas negara tidak diperhitungkan | Untung tergerus diam-diam |

## 1.1 Biaya pembayaran lintas negara

| Komponen | Besaran |
|---|---|
| MDR kartu kredit | 2,9% + Rp 2.000 |
| Tambahan kartu luar negeri | 1–2% |
| Spread konversi mata uang | 1–2% |
| **Total efektif** | **±5–6% per transaksi** |
| Transfer bank / VA (pembanding) | ±Rp 4.000 flat |
| QRIS (pembanding) | 0,7% |

> **Keputusan produk:** kartu internasional hanya untuk DP, pelunasan lewat transfer. Sistem wajib mendukung dua kanal berbeda dalam satu booking sejak Fase 1.

## 1.2 Cabang Bali dan Jakarta

| Modul | Bali | Jakarta | Catatan |
|---|---|---|---|
| Package Builder | Utama | Belum pasti | Asumsi V1 |
| Driver & Armada | Utama | Belum pasti | Asumsi V6 |
| Finance & Rekonsiliasi | Perlu | **Utama** | Penekanan Jakarta |
| Payment Gateway | Fase 2 | **Utama** | Penekanan Jakarta |
| Katalog, Booking, Lead | Perlu | Perlu | Dipakai bersama |

> **WAJIB:** setiap tabel transaksional punya kolom `branch_id` sejak migrasi pertama. Tidak bisa ditambahkan belakangan tanpa migrasi data besar.

## 1.3 Non-goals MVP

- Integrasi API maskapai/OTA real-time
- Portal login khusus driver
- Aplikasi mobile native
- Modul akuntansi penuh (hanya ekspor data)
- Sinkronisasi inventori kamar real-time

---

# 2. Tech Stack

| Lapisan | Teknologi | Catatan |
|---|---|---|
| Backend | Laravel 12, PHP 8.2+ | Struktur modular per domain |
| Auth | Laravel Fortify | Scaffold awal dari Breeze |
| UI | Livewire 4 | Satu komponen per layar fungsional |
| Interaktivitas klien | Alpine.js | State lokal, tanpa round-trip |
| Styling | Tailwind CSS 3 | Design token di config |
| Build | Vite 7 | — |
| HTTP klien | Axios | Hanya endpoint non-Livewire |
| RBAC | spatie/laravel-permission | Dikombinasikan dengan Policy |
| Audit | spatie/laravel-activitylog | Wajib di seluruh aksi sensitif |
| i18n | mcamara/laravel-localization | Prefix locale pada URL |
| Konten multibahasa | spatie/laravel-translatable | Kolom JSON pada model konten |
| Database | MySQL 8 | InnoDB, utf8mb4 |
| Testing | PHPUnit 11 | Feature + Livewire test |
| Kualitas kode | Pint | Dijalankan sebelum commit |
| Dev | Sail, Pail, Tinker | — |

## 2.1 Struktur direktori

```
app/
  Domain/
    Catalog/       Partner, InventoryItem, Rate
    Pricing/       PricingEngine, RuleResolver, Money
    Currency/      RateStore, Converter
    Packaging/     Package, PackageCalculator
    Sales/         Lead, Quotation
    Booking/       Booking, BookingStateMachine, Guest
    Fleet/         Driver, Vehicle, AssignmentService
    Finance/       Payment, Reconciliation, PaymentProvider/
    Reporting/
  Livewire/
    Admin/         Komponen panel internal
    Public/        Komponen storefront
  Support/         Audit, Branch, Localization
resources/
  views/livewire/
  lang/            id/, en/, ar/
tests/
  Feature/         Per modul
  Unit/            PricingEngine, Money, StateMachine
```

## 2.2 Konvensi wajib

- Perhitungan harga **hanya** di `PricingEngine`. Dilarang di komponen Livewire atau Blade.
- Perubahan status booking **hanya** lewat `BookingStateMachine`. Dilarang update kolom langsung.
- Nilai uang = `BIGINT` satuan terkecil + kolom `currency`. **Dilarang float.**
- Setiap aksi sensitif lewat Policy. Dilarang cek nama role manual.
- Setiap query transaksional difilter `branch_id` lewat global scope.
- Operasi lambat (PDF, email, notifikasi) masuk queue.
- Tidak ada teks antarmuka di Blade. Seluruhnya lewat file lang.
- Gunakan logical properties Tailwind: `ms`, `me`, `ps`, `pe`. **Dilarang** `ml`, `mr`, `pl`, `pr`.

---

# 3. Urutan Pengerjaan

| Kode | Modul | Bergantung | Fase |
|---|---|---|---|
| M0 | Fondasi & Setup | — | 1 |
| M1 | Auth, RBAC & Multi-cabang | M0 | 1 |
| M2 | i18n, RTL & Design Token | M0 | 1 |
| M3 | Katalog & Inventori | M1, M2 | 1 |
| M4 | Pricing & Currency Engine | M3 | 1 |
| M5 | Package Builder | M4 | 1 |
| M6 | Lead & Quotation | M5 | 1 |
| M7 | Booking & Order | M6 | 1 |
| M8 | Driver & Armada | M7 | 1 |
| M9 | Finance & Pembayaran | M7 | 1 |
| M10 | Storefront Publik | M5, M2 | 1 |
| M11 | Laporan & Dashboard | M7, M9 | 1 |
| M12 | Audit & Hardening | Semua | 1 |
| M13 | Integrasi Payment Gateway | M9 | 2 |
| M14 | Portal Agen B2B | M4, M7 | 2 |
| M15 | Portal Driver & API Maskapai | M8 | 3 |

> M8 dan M9 tidak saling bergantung — bisa paralel dua orang setelah M7 selesai.

---

# 4. Design System & Color Palette

## 4.1 Dasar pemilihan

Identitas visual Indogate yang sudah berjalan memakai **merah dan biru**. Deck Raynad menetapkan arah **hitam/charcoal/putih**. Dua arahan ini diselesaikan dengan pembagian peran:

| Peran | Warna | Porsi layar |
|---|---|---|
| **Brand primer** | Merah | ~10% — aksi utama, aksen, penanda |
| **Brand sekunder** | Biru | ~3% — sentuhan tipis, state informasi, tautan |
| **Netral** | Charcoal → putih | ~85% — teks, latar, border, struktur |
| **Semantik** | Hijau, kuning, merah | ~2% — status sistem |

> **Kenapa proporsinya timpang.** Tiga warna brand tidak jelek — yang jelek adalah tiga warna dengan porsi seimbang. Merah + biru + putih dalam porsi sama akan terbaca seperti bendera atau situs pemerintah, bukan travel premium. Merah dominan sebagai aksen di atas kanvas netral inilah yang membuatnya terasa mahal.

## 4.2 Skala warna

Semua nilai sudah diuji kontras WCAG. Kolom "vs putih" adalah rasio kontras terhadap `#FFFFFF`.

### Red — brand primer

| Token | Hex | vs putih | Pakai untuk |
|---|---|---|---|
| `red-50` | `#FEF2F3` | 1.09 | Latar notifikasi lembut |
| `red-100` | `#FDE3E5` | 1.21 | Latar badge |
| `red-200` | `#FBC9CE` | 1.46 | Border lembut |
| `red-300` | `#F79AA4` | 2.08 | Disabled state |
| `red-400` | `#EF6274` | 3.15 | Aksen pada latar gelap |
| `red-500` | `#DC2F45` | 4.63 | Hover tombol |
| **`red-600`** | **`#C41230`** | **6.04** | **Warna aksi utama — tombol, link aktif** |
| `red-700` | `#A50E28` | 7.80 | Tombol ditekan, teks di atas putih |
| `red-800` | `#871024` | 9.86 | Heading beraksen |
| `red-900` | `#701223` | 11.67 | Teks kontras tinggi |

### Blue — brand sekunder

| Token | Hex | vs putih | Pakai untuk |
|---|---|---|---|
| `blue-50` | `#F0F5FB` | 1.10 | Latar panel informasi |
| `blue-100` | `#DEE9F6` | 1.23 | Badge informasi |
| `blue-200` | `#C0D6EE` | 1.49 | Border panel |
| `blue-300` | `#92B8DF` | 2.07 | Aksen dekoratif |
| `blue-400` | `#5C93CC` | 3.23 | Aksen pada latar gelap |
| `blue-500` | `#3573B4` | 4.93 | Hover tautan |
| **`blue-600`** | **`#1F5896`** | **7.26** | **Tautan, state informasi** |
| `blue-700` | `#1A467A` | 9.54 | Tautan ditekan |
| `blue-800` | `#183C65` | 11.21 | Aksen gelap |
| `blue-900` | `#173455` | 12.66 | Teks kontras tinggi |

### Neutral — struktur (charcoal hangat, bukan abu dingin)

| Token | Hex | vs putih | Pakai untuk |
|---|---|---|---|
| `neutral-0` | `#FFFFFF` | 1.00 | Latar kartu |
| `neutral-50` | `#F8F8F7` | 1.06 | Latar halaman |
| `neutral-100` | `#F0F0EF` | 1.14 | Latar bergantian tabel |
| `neutral-200` | `#E2E2E0` | 1.30 | Border, pemisah |
| `neutral-300` | `#C8C8C5` | 1.68 | Border input |
| `neutral-400` | `#9B9B97` | 2.79 | Placeholder |
| `neutral-500` | `#74746F` | 4.70 | Teks sekunder |
| `neutral-600` | `#575753` | 7.26 | Teks pendukung |
| `neutral-700` | `#414140` | 10.22 | Teks isi |
| `neutral-800` | `#2B2B2A` | 14.17 | Heading |
| **`neutral-900`** | **`#1A1A19`** | **17.42** | **Teks utama, latar footer** |

> Netral sengaja dipilih **hangat** (sedikit ke arah cokelat), bukan abu biru. Abu dingin membuat merah terlihat murahan; netral hangat membuatnya terlihat seperti kulit dan kayu — cocok untuk brand travel premium.

### Semantik — status sistem

| Token | Hex | vs putih | Pakai untuk |
|---|---|---|---|
| `success` | `#1E7D5A` | 5.08 | Lunas, terverifikasi, aktif |
| `warning` | `#B45309` | 5.02 | Menunggu, hampir jatuh tempo |
| `danger` | `#C41230` | 6.04 | Gagal, dibatalkan, error |
| `info` | `#1F5896` | 7.26 | Catatan, panduan |

> `danger` sengaja sama dengan `red-600`. Konteks yang membedakan, bukan warna. Menambah merah kedua hanya membuat palet kotor.

## 4.3 Aturan penggunaan

**Yang wajib:**

- Satu layar hanya boleh punya **satu** tombol `red-600`. Kalau ada dua tombol merah, pengguna tidak tahu mana yang utama.
- Biru **tidak pernah** dipakai untuk tombol aksi. Hanya tautan, panel informasi, dan aksen tipis.
- Latar halaman selalu `neutral-50` atau `neutral-0`. Dilarang latar berwarna penuh.
- Teks isi minimal `neutral-700`. Teks sekunder minimal `neutral-500`.
- Border default `neutral-200`. Dilarang border merah kecuali untuk state error.

**Yang dilarang:**

- Gradien merah-ke-biru. Ini langsung terbaca sebagai template generik.
- Merah sebagai latar section besar. Merah punya energi tinggi; dalam porsi besar terasa murah dan melelahkan.
- Warna di luar token ini. Kalau butuh warna baru, tambahkan ke config dulu, jangan hardcode.
- Warna sebagai satu-satunya penanda makna. Selalu sertakan ikon atau label — syarat WCAG.

## 4.4 Tailwind config

```js
// tailwind.config.js
export default {
  theme: {
    extend: {
      colors: {
        red: {
          50:'#FEF2F3', 100:'#FDE3E5', 200:'#FBC9CE', 300:'#F79AA4', 400:'#EF6274',
          500:'#DC2F45', 600:'#C41230', 700:'#A50E28', 800:'#871024', 900:'#701223',
        },
        blue: {
          50:'#F0F5FB', 100:'#DEE9F6', 200:'#C0D6EE', 300:'#92B8DF', 400:'#5C93CC',
          500:'#3573B4', 600:'#1F5896', 700:'#1A467A', 800:'#183C65', 900:'#173455',
        },
        neutral: {
          0:'#FFFFFF', 50:'#F8F8F7', 100:'#F0F0EF', 200:'#E2E2E0', 300:'#C8C8C5',
          400:'#9B9B97', 500:'#74746F', 600:'#575753', 700:'#414140', 800:'#2B2B2A', 900:'#1A1A19',
        },
        success:'#1E7D5A',
        warning:'#B45309',
        danger:'#C41230',
        info:'#1F5896',
      },
      fontFamily: {
        display: ['[FONT_DISPLAY]', 'Georgia', 'serif'],
        body:    ['[FONT_BODY]', 'system-ui', 'sans-serif'],
        arabic:  ['IBM Plex Sans Arabic', 'Noto Kufi Arabic', 'sans-serif'],
      },
      borderRadius: {
        sm:'2px', DEFAULT:'4px', md:'6px', lg:'8px', xl:'12px',
      },
      boxShadow: {
        sm:'0 1px 2px 0 rgb(26 26 25 / 0.04)',
        DEFAULT:'0 1px 3px 0 rgb(26 26 25 / 0.08)',
        md:'0 4px 12px -2px rgb(26 26 25 / 0.10)',
        lg:'0 12px 28px -6px rgb(26 26 25 / 0.14)',
      },
    },
  },
}
```

> **Radius sengaja kecil.** `rounded-2xl` di semua tempat adalah ciri paling khas tampilan template generik. Radius 4–8px terbaca lebih editorial dan lebih premium.

## 4.5 Pemetaan komponen

| Komponen | Kelas |
|---|---|
| Tombol utama | `bg-red-600 text-white hover:bg-red-500 active:bg-red-700` |
| Tombol sekunder | `border border-neutral-300 text-neutral-800 hover:bg-neutral-100` |
| Tombol teks | `text-red-700 hover:text-red-800 hover:underline` |
| Tombol destruktif | `bg-danger text-white` + konfirmasi wajib |
| Tautan | `text-blue-600 hover:text-blue-700 underline-offset-2` |
| Input | `border-neutral-300 focus:border-red-600 focus:ring-2 focus:ring-red-600/20` |
| Input error | `border-danger` + pesan teks |
| Kartu | `bg-neutral-0 border border-neutral-200 rounded-lg shadow-sm` |
| Kartu unggulan | `bg-neutral-0 border-2 border-red-600 rounded-lg shadow-md` |
| Header tabel | `bg-neutral-900 text-white` |
| Baris bergantian | `odd:bg-neutral-0 even:bg-neutral-50` |
| Badge lunas | `bg-success/10 text-success border border-success/20` |
| Badge menunggu | `bg-warning/10 text-warning border border-warning/20` |
| Badge batal | `bg-danger/10 text-danger border border-danger/20` |
| Panel informasi | `bg-blue-50 border-s-4 border-blue-600` |
| Fokus keyboard | `focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600` |

> Perhatikan `border-s-4` pada panel informasi — **logical property**, bukan `border-l-4`. Dalam mode Arab, garisnya otomatis pindah ke kanan.

## 4.6 Status booking → warna

| Status | Warna | Kelas badge |
|---|---|---|
| `draft` | Netral | `bg-neutral-100 text-neutral-600` |
| `quoted` | Info | `bg-blue-50 text-blue-700` |
| `expired` | Netral gelap | `bg-neutral-200 text-neutral-700` |
| `confirmed` | Brand | `bg-red-50 text-red-700` |
| `partially_paid` | Warning | `bg-warning/10 text-warning` |
| `paid` | Success | `bg-success/10 text-success` |
| `in_progress` | Info | `bg-blue-100 text-blue-800` |
| `completed` | Success gelap | `bg-success text-white` |
| `cancelled` | Danger | `bg-danger/10 text-danger` |

## 4.7 Tipografi

| Token | Ukuran | Line height | Pakai untuk |
|---|---|---|---|
| `text-xs` | 12px | 1.5 | Label, caption |
| `text-sm` | 14px | 1.5 | Teks tabel, teks pendukung |
| `text-base` | 16px | 1.6 | Teks isi |
| `text-lg` | 18px | 1.5 | Teks menonjol |
| `text-xl` | 20px | 1.4 | Judul kartu |
| `text-2xl` | 24px | 1.3 | Judul section |
| `text-3xl` | 30px | 1.2 | Judul halaman |
| `text-5xl` | 48px | 1.1 | Hero |

**Ketentuan bahasa Arab:**

- Font Arab wajib punya fallback terbaca: IBM Plex Sans Arabic atau Noto Kufi Arabic.
- Naskah Arab butuh line-height lebih longgar. Tambahkan sekitar `0.15` pada semua nilai saat locale `ar` aktif.
- Jangan pakai font display Latin untuk teks Arab. Sediakan pasangan tersendiri.

## 4.8 Mode gelap

Tidak masuk MVP. Tapi karena token sudah tersusun berpasangan, penambahannya nanti hanya perlu membalik skala netral dan menggeser aksi utama ke `red-500`. Jangan hardcode warna apa pun, agar pintu ini tetap terbuka.

---

# M0 — Fondasi & Setup

**Tujuan:** menyiapkan kerangka proyek, dependensi, konvensi, dan pipeline pengujian sebelum fitur apa pun dibangun.

### Langkah

1. Buat proyek Laravel 12, jalankan Sail, pastikan PHP 8.2+.
2. Pasang Breeze (stack Livewire) sebagai kerangka. Jangan dipakai apa adanya.
3. Pasang Fortify, pindahkan pengaturan auth ke sana. Nonaktifkan registrasi publik untuk panel admin.
4. Pasang livewire/livewire 4, spatie/laravel-permission, spatie/laravel-activitylog, mcamara/laravel-localization, spatie/laravel-translatable.
5. Publish konfigurasi paket, jalankan migrasi bawaan.
6. Buat struktur `app/Domain` sesuai Bagian 2.1.
7. Terapkan design token Bagian 4.4 ke `tailwind.config.js`.
8. Konfigurasi Vite 7, pastikan hot reload jalan dengan Livewire.
9. Buat `.env.testing` dengan database terpisah. **Jangan pernah menguji di database pengembangan.**
10. Konfigurasi PHPUnit 11: `RefreshDatabase`, environment testing.
11. Pasang Pint preset laravel, tambahkan skrip composer.
12. Buat satu test sanity, pastikan lulus.
13. Seeder dasar: satu user per peran, branch Bali, branch Jakarta.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `tests/Feature/SanityTest.php` | Halaman utama merespons 200 |
| `tests/Feature/Auth/LoginTest.php` | User valid login; password salah ditolak |
| `tests/Unit/EnvironmentTest.php` | Environment testing memakai DB terpisah |

```bash
php artisan test
./vendor/bin/pint --test
npm run build
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | `sail up`, buka di browser | Halaman terbuka tanpa error |
| 2 | Login dengan user seeder | Masuk ke dashboard kosong |
| 3 | Ubah token warna di config, reload | Perubahan tampak tanpa build manual |
| 4 | `php artisan test` | Lulus, DB pengembangan tidak berubah |
| 5 | `pint --test` | Tidak ada pelanggaran |
| 6 | `php artisan pail`, picu error | Log muncul realtime |

### Definition of Done

- [ ] Seluruh dependensi terpasang dengan versi yang ditetapkan
- [ ] Struktur domain ada dan terdokumentasi di README
- [ ] Design token Bagian 4 sudah masuk config
- [ ] Pengujian berjalan di database terpisah
- [ ] Pint lulus
- [ ] Seeder menghasilkan user tiap peran dan dua cabang

---

# M1 — Auth, RBAC & Multi-cabang

**Tujuan:** peran, izin, dan pemisahan data antar cabang. Fondasi keamanan seluruh sistem.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Peran: Super Admin, CS Admin, Finance Admin, Customer | Wajib |
| Izin granular per aksi | Wajib |
| Penugasan user ke cabang | Wajib |
| Global scope filter `branch_id` | Wajib |
| Super Admin dapat berpindah cabang | Wajib |
| Segregasi CS dan Finance | Wajib |
| Halaman kelola user & peran | Wajib |
| 2FA untuk admin | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `branches` | id, name, code, timezone, is_active |
| `users` | id, name, email, branch_id, is_active |
| `roles` / `permissions` | bawaan spatie |

### Matriks izin

| Izin | Super | CS | Finance |
|---|---|---|---|
| `catalog.manage` | Ya | Ya | Tidak |
| `pricing.manage` | Ya | Tidak | Tidak |
| `currency.manage` | Ya | Tidak | Ya |
| `lead.manage` | Ya | Ya | Tidak |
| `quotation.create` | Ya | Ya | Tidak |
| `booking.manage` | Ya | Ya | Lihat saja |
| `payment.verify` | Ya | Tidak | Ya |
| `driver.assign` | Ya | Ya | Tidak |
| `report.margin.view` | Ya | Sebagian | Ya |
| `user.manage` | Ya | Tidak | Tidak |
| `activitylog.view` | Ya | Tidak | Tidak |
| `branch.switch` | Ya | Tidak | Tidak |

> **Aturan mutlak:** satu akun tidak boleh punya `booking.manage` dan `payment.verify` sekaligus, kecuali Super Admin. Ditegakkan di kode, bukan hanya di dokumen.

### Langkah

1. Migrasi tabel `branches` + seeder Bali dan Jakarta.
2. Tambah `branch_id` pada `users` beserta relasi.
3. Definisikan seluruh permission di seeder, penamaan `domain.aksi`.
4. Definisikan empat peran dan pasangkan permission sesuai matriks.
5. Buat trait `BelongsToBranch` berisi global scope filter `branch_id`.
6. Terapkan trait pada seluruh model transaksional.
7. Middleware penetap cabang aktif tiap request.
8. Fitur pindah cabang untuk Super Admin, simpan di session.
9. Policy untuk setiap model inti. **Dilarang cek nama role manual.**
10. Komponen Livewire kelola user: daftar, tambah, ubah peran, nonaktifkan.
11. Tegakkan segregasi tugas di level validasi.
12. Catat perubahan peran dan cabang ke activity log.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `RoleAssignmentTest.php` | Peran tersimpan; peran ganda terlarang ditolak |
| `BranchScopeTest.php` | User Bali tidak lihat data Jakarta; Super Admin bisa pindah |
| `PermissionTest.php` | Tiap peran hanya akses rute yang diizinkan |
| `SegregationOfDutiesTest.php` | `booking.manage` + `payment.verify` ditolak |
| `UserManagementTest.php` | Hanya Super Admin buka halaman kelola user |

```bash
php artisan test --filter="Auth|Branch|Permission|Segregation"
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Login CS Bali, buka daftar data | Hanya data Bali |
| 2 | Akses URL data Jakarta langsung | **403**, bukan halaman kosong |
| 3 | Super Admin ganti cabang ke Jakarta | Data berganti |
| 4 | CS Admin buka halaman kelola user | Menu hilang, URL ditolak |
| 5 | Beri peran Finance ke user yang sudah CS | Validasi menolak dengan pesan jelas |
| 6 | Ubah peran user, buka activity log | Tercatat lengkap dengan aktor dan waktu |
| 7 | Nonaktifkan user, coba login | Ditolak |

### Definition of Done

- [ ] Empat peran dan seluruh permission tersedia lewat seeder
- [ ] Global scope cabang aktif di seluruh model transaksional
- [ ] Akses lintas cabang ditolak dengan 403
- [ ] Segregasi tugas ditegakkan di kode dan teruji
- [ ] Perubahan peran tercatat di activity log

---

# M2 — i18n, RTL & Design Token

**Tujuan:** tiga bahasa dan RTL disiapkan **sebelum** antarmuka dibangun. Menambahkan RTL setelah puluhan halaman selesai jauh lebih mahal.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Tiga locale: Arab, Inggris, Indonesia | Wajib |
| Prefix locale pada URL | Wajib |
| Atribut `dir` otomatis pada `<html>` | Wajib |
| Komponen Blade dasar yang sadar RTL | Wajib |
| Konten multibahasa pada model | Wajib |
| Format angka, tanggal, mata uang per locale | Wajib |
| Pemilih bahasa dan mata uang | Wajib |

### Ketentuan RTL

- Gunakan `ms`, `me`, `ps`, `pe`, `text-start`, `text-end`. **Dilarang** `ml`, `mr`, `pl`, `pr`, `text-left`, `text-right`.
- Ikon panah dan chevron dibalik otomatis saat locale `ar`.
- Font Arab wajib punya fallback terbaca.
- Line-height Arab +0.15 dari nilai Latin.
- Setiap komponen diuji di tiga bahasa sebelum dianggap selesai.

> **Paling sering gagal.** RTL adalah bagian yang paling sering rusak dan paling jarang diperiksa. Jadikan pemeriksaan tiga bahasa sebagai DoD **setiap modul UI**, bukan hanya modul ini.

### Langkah

1. Konfigurasi mcamara/laravel-localization, tiga locale, prefix URL.
2. Middleware penetap locale dan arah teks.
3. Struktur `lang/id`, `lang/en`, `lang/ar`, file terpisah per domain.
4. Atribut `dir` pada layout utama.
5. Verifikasi design token Bagian 4 sudah lengkap di config.
6. Komponen Blade dasar sadar RTL: tombol, input, kartu, tabel, modal, dropdown, badge.
7. Helper format mata uang (menerima currency + locale).
8. Helper format tanggal per locale.
9. Pasang spatie/laravel-translatable pada model konten.
10. Pemilih bahasa dan mata uang di layout.
11. Perintah artisan pendeteksi kunci terjemahan kosong.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `LocalizationTest.php` | Prefix URL mengubah locale; locale tak dikenal dialihkan |
| `RtlDirectionTest.php` | `ar` → `dir=rtl`; lainnya → `dir=ltr` |
| `MoneyFormatTest.php` | Format SAR, USD, IDR benar per locale |
| `TranslationCompletenessTest.php` | Tidak ada kunci kosong di tiga bahasa |

```bash
php artisan test --filter="Localization|Rtl|Money|Translation"
php artisan lang:missing
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Ganti bahasa ke Arab | Halaman berbalik arah, tidak ada elemen tumpang tindih |
| 2 | Periksa tiap komponen dasar mode Arab | Ikon panah menghadap arah benar |
| 3 | Ganti ke Inggris lalu Indonesia | Tidak ada teks tertinggal di bahasa lain |
| 4 | Ganti mata uang ke SAR | Format angka dan simbol sesuai konvensi |
| 5 | Perkecil ke lebar ponsel, mode Arab | Rapi, tidak ada scroll horizontal |
| 6 | `grep` teks antarmuka di Blade | Tidak ditemukan di luar file lang |
| 7 | `grep -rn "\bml-\|\bmr-\|\bpl-\|\bpr-"` di views | Tidak ada hasil |

### Definition of Done

- [ ] Tiga locale berfungsi dengan prefix URL
- [ ] Mode Arab menghasilkan RTL benar di seluruh komponen dasar
- [ ] Design token lengkap, tidak ada warna hardcoded
- [ ] Tidak ada teks antarmuka di luar file lang
- [ ] Tidak ada physical property tersisa di views

---

# M3 — Katalog & Inventori

**Tujuan:** mengelola seluruh produk yang dijual beserta harga modalnya.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| CRUD mitra: hotel, villa, vendor kendaraan | Wajib |
| CRUD item: kamar, kendaraan, tiket, aktivitas | Wajib |
| Harga modal per item per periode | Wajib |
| Galeri foto per item | Wajib |
| Konten multibahasa pada nama dan deskripsi | Wajib |
| Penanda aktif/nonaktif | Wajib |
| Blackout date | Sebaiknya |
| Import massal dari Excel | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `partners` | id, branch_id, name(JSON), type, city, contact, is_active |
| `inventory_items` | id, branch_id, partner_id, type, name(JSON), description(JSON), capacity, is_active |
| `rates` | id, inventory_item_id, valid_from, valid_to, cost_minor, currency |
| `item_media` | id, inventory_item_id, path, sort_order |
| `blackout_dates` | id, inventory_item_id, date, reason |

### Langkah

1. Migrasi seluruh tabel, sertakan `branch_id` dan indeks.
2. Model dengan `BelongsToBranch` dan `HasTranslations`.
3. Policy tiap model, kaitkan dengan `catalog.manage`.
4. Livewire daftar mitra: pencarian, filter, paginasi.
5. Form tambah/ubah mitra dengan validasi lengkap.
6. Pengelolaan item inventori beserta relasi ke mitra.
7. Pengelolaan harga modal per periode, **cegah tumpang tindih**.
8. Unggah galeri dengan kompresi dan batasan ukuran.
9. Penanda aktif/nonaktif, **bukan** penghapusan permanen.
10. Catat perubahan harga modal ke activity log.
11. Seeder data contoh untuk modul berikutnya.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `PartnerCrudTest.php` | Tambah, ubah, nonaktifkan; validasi tolak input kosong |
| `InventoryItemTest.php` | Item terhubung mitra benar; lintas cabang tidak terlihat |
| `RateOverlapTest.php` | Periode tumpang tindih ditolak |
| `CatalogPermissionTest.php` | Finance Admin tidak bisa ubah katalog |
| `TranslatableFieldTest.php` | Nama tersimpan benar di tiga bahasa |

```bash
php artisan test --filter=Catalog
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Tambah hotel lengkap tiga bahasa | Nama tampil sesuai bahasa aktif |
| 2 | Tambah dua tipe kamar harga berbeda | Keduanya tampil di daftar hotel |
| 3 | Tambah periode harga tumpang tindih | Ditolak, pesan menjelaskan bentroknya |
| 4 | Unggah lima foto ke satu kamar | Semua tampil, urutan bisa diubah |
| 5 | Finance Admin buka menu katalog | Menu hilang, akses langsung ditolak |
| 6 | Ubah harga modal, buka activity log | Tercatat nilai lama dan baru |
| 7 | Nonaktifkan hotel | Hilang dari pilihan paket, data lama utuh |

### Definition of Done

- [ ] Seluruh entitas katalog dapat dikelola lewat antarmuka
- [ ] Harga modal per periode bebas tumpang tindih
- [ ] Konten tersimpan dalam tiga bahasa
- [ ] Pembatasan peran dan cabang berfungsi
- [ ] Perubahan harga tercatat di activity log

---

# M4 — Pricing & Currency Engine

**Tujuan:** menghitung harga jual dari harga modal, menerapkan margin per musim, mengunci kurs. **Pusat keuangan sistem — cakupan pengujian tertinggi.**

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Aturan margin per musim dan tipe produk | Wajib |
| Kalender musim: peak, high, low | Wajib |
| Input dan riwayat kurs | Wajib |
| Penguncian kurs pada penawaran | Wajib |
| Biaya kanal pembayaran masuk perhitungan margin | Wajib |
| Tampilan harga multi-mata-uang | Wajib |
| Override harga manual beserta alasan | Wajib |
| Simulasi dampak perubahan margin | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `currencies` | code, symbol, decimal_places, is_active |
| `exchange_rates` | id, currency, rate, effective_from, created_by |
| `seasons` | id, branch_id, name, date_from, date_to, type |
| `pricing_rules` | id, branch_id, product_type, season_type, margin_percent, is_active |
| `payment_channel_costs` | id, channel, percent_fee, flat_fee_minor, currency |

### Rumus

```
cost_total      = SUM(item.cost_minor x qty)
margin_percent  = rule(product_type, season(departure_date))
margin_minor    = cost_total x margin_percent
channel_cost    = cost_total x channel.percent + channel.flat
sell_idr_minor  = cost_total + margin_minor + channel_cost
display_price   = sell_idr_minor / locked_rate(currency)
```

- `margin_percent` diambil dari musim pada **tanggal keberangkatan**, bukan tanggal pembuatan penawaran.
- `locked_rate` disimpan pada penawaran, tidak pernah diambil ulang.
- `channel_cost` dihitung dari kanal pembayaran yang dipilih tamu.

> **WAJIB:** seluruh nilai uang `BIGINT` satuan terkecil. Pembulatan hanya sekali, di akhir, pada lapisan tampilan. **Dilarang float di titik mana pun.**

### Langkah

1. Value object `Money` (amount_minor + currency) dengan operasi aman.
2. Cast Laravel untuk `Money`.
3. Migrasi dan model seluruh tabel di atas.
4. Kelas `RuleResolver` penentu margin.
5. Kelas `Converter` pengubah nilai antar mata uang.
6. Kelas `PricingEngine` — kembalikan **rincian** perhitungan, bukan hanya angka akhir.
7. Antarmuka pengelolaan kurs, simpan riwayat, **jangan timpa nilai lama**.
8. Antarmuka kalender musim per cabang.
9. Antarmuka aturan margin dengan pratinjau dampak.
10. Antarmuka biaya kanal pembayaran.
11. Activity log pada tiap perubahan kurs, musim, aturan margin.
12. **Tulis unit test seluruh rumus lebih dulu**, sebelum menghubungkan ke antarmuka.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `MoneyTest.php` | Aritmetika tidak kehilangan presisi; mata uang beda tidak bisa dijumlahkan |
| `PricingEngineTest.php` | Benar untuk peak, high, low; margin nol; komponen kosong |
| `RuleResolverTest.php` | Aturan dipilih dari tanggal keberangkatan, bukan tanggal pembuatan |
| `ConverterTest.php` | Konversi benar; kurs tidak ditemukan melempar exception |
| `ChannelCostTest.php` | Kartu internasional dan transfer menghasilkan harga berbeda |
| `RateManagementTest.php` | Kurs lama tidak tertimpa; hanya peran berwenang |
| `SeasonOverlapTest.php` | Musim tumpang tindih ditolak |

```bash
php artisan test --filter="Money|Pricing|Rule|Converter|Channel|Season"
php artisan test --coverage --min=90 --filter=Pricing
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Aturan margin 20% low, 35% peak | Keduanya tersimpan |
| 2 | Hitung paket berangkat musim low | Harga sesuai margin 20% |
| 3 | Ubah tanggal ke musim peak saja | Harga naik sesuai margin 35% |
| 4 | Pilih kanal kartu internasional | Harga naik ±5–6% dibanding transfer |
| 5 | Ubah kurs SAR, buka penawaran lama | **Harga tidak berubah** |
| 6 | Buat penawaran baru | Harga mengikuti kurs terbaru |
| 7 | Override harga tanpa alasan | Ditolak |
| 8 | Buka activity log setelah ubah margin | Nilai lama, nilai baru, aktor, waktu |
| 9 | CS Admin coba ubah aturan margin | Ditolak |

### Definition of Done

- [ ] Cakupan pengujian `PricingEngine` minimal 90%
- [ ] Tidak ada float di seluruh jalur perhitungan uang
- [ ] Kurs terkunci terbukti tidak berubah
- [ ] Biaya kanal pembayaran ikut diperhitungkan
- [ ] Override manual selalu disertai alasan dan tercatat

---

# M5 — Package Builder

**Tujuan:** menyusun paket multi-komponen dan multi-hotel dengan harga yang berubah seiring penyuntingan. **Pembeda utama sistem** dibanding perangkat lunak OTA umum.

> **Cabang:** modul utama Bali. Kebutuhan Jakarta belum terkonfirmasi (asumsi V1).

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Susun paket dari banyak komponen | Wajib |
| Multi-hotel dengan jumlah malam berbeda | Wajib |
| Perhitungan per pax dan konfigurasi kamar | Wajib |
| Kalkulasi margin dan harga jual saat penyuntingan | Wajib |
| Simpan sebagai template | Wajib |
| Duplikasi paket | Wajib |
| Sembunyikan margin dari peran tertentu | Wajib |
| Timeline itinerary per hari | Sebaiknya |
| Ekspor itinerary PDF tiga bahasa | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `packages` | id, branch_id, name(JSON), description(JSON), is_template, base_pax |
| `package_items` | id, package_id, inventory_item_id, day_from, day_to, qty, nights |
| `package_days` | id, package_id, day_number, title(JSON), notes(JSON) |

> **PERHATIAN TEKNIS.** Livewire mengirim request tiap interaksi. Kalau setiap perubahan komponen memicu hitung ulang di server, penyusunan paket akan terasa lambat. **Gunakan Alpine** untuk menambah/menghapus baris di sisi klien, panggil server hanya saat satu blok selesai disunting atau tombol hitung ulang ditekan. Terapkan `wire:model.live.debounce` pada input angka.

> **KEBOCORAN PAYLOAD.** Di Livewire, properti komponen ikut terkirim ke klien. Menyembunyikan harga modal dengan `@can` di Blade **tidak cukup** — angkanya tetap ada di payload. Filter di sisi server sebelum data masuk ke properti komponen.

### Langkah

1. Migrasi dan model `packages`, `package_items`, `package_days`.
2. Kelas `PackageCalculator` yang memanggil `PricingEngine`, kembalikan rincian per komponen.
3. **Unit test `PackageCalculator` lebih dulu**, sebelum antarmuka.
4. Livewire builder: dua kolom — penyusun di kiri, ringkasan harga di kanan.
5. Alpine untuk tambah/hapus/susun ulang baris tanpa request server.
6. Panggil perhitungan server saat blok selesai disunting, dengan debounce.
7. Validasi: total malam konsisten dengan durasi paket.
8. Pemilih komponen dengan pencarian, difilter cabang dan status aktif.
9. Rincian harga: modal, margin, biaya kanal, harga jual. **Filter di server** untuk peran tanpa izin.
10. Simpan sebagai template dan duplikasi.
11. Timeline itinerary per hari, konten tiga bahasa.
12. Ekspor PDF **lewat queue**.
13. Ukur waktu respons dengan sepuluh komponen, pastikan di bawah satu detik.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `PackageCalculatorTest.php` | Paket dua hotel (4+2 malam) total benar |
| `PackagePaxTest.php` | Perubahan pax mengubah harga sesuai aturan |
| `BuilderTest.php` | Komponen Livewire tambah/hapus item benar |
| `MarginVisibilityTest.php` | **Harga modal tidak ada di payload** untuk peran tanpa izin |
| `TemplateTest.php` | Duplikasi menghasilkan salinan independen |
| `ValidationTest.php` | Total malam tidak konsisten ditolak |

```bash
php artisan test --filter=Package
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Susun paket 7 hari: hotel A 4 malam, hotel B 2 malam, 1 kendaraan | Seluruh komponen tampil di ringkasan |
| 2 | Ubah malam hotel A dari 4 ke 5 | Total berubah, muncul peringatan durasi |
| 3 | Ubah tamu dari 2 ke 4 | Harga menyesuaikan tanpa susun ulang |
| 4 | Tambah dan hapus komponen 10x berturut | Tetap responsif, tidak ada jeda mengganggu |
| 5 | Login CS tanpa izin margin | Kolom modal dan margin tidak tampil |
| 6 | **Periksa respons jaringan di DevTools** | Harga modal juga tidak ada di payload |
| 7 | Simpan template lalu duplikasi | Salinan bisa diubah tanpa memengaruhi asli |
| 8 | Buka builder mode Arab | Dua kolom berbalik arah dengan benar |
| 9 | Ekspor itinerary PDF | Dihasilkan lewat queue, halaman tidak menggantung |

### Definition of Done

- [ ] Paket multi-hotel dapat disusun dan dihitung benar
- [ ] Waktu respons builder di bawah 1 detik untuk 10 komponen
- [ ] Harga modal tidak pernah terkirim ke peran tidak berhak
- [ ] Template dan duplikasi bekerja independen
- [ ] Builder rapi dalam mode Arab

---

# M6 — Lead & Quotation

**Tujuan:** menangkap seluruh permintaan masuk dan mengubahnya jadi penawaran resmi dengan harga dan kurs terkunci.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Pencatatan lead manual dan dari formulir web | Wajib |
| Status lead dan alasan kalah | Wajib |
| Penugasan lead ke CS tertentu | Wajib |
| Riwayat interaksi per lead | Wajib |
| Pembuatan penawaran dari paket | Wajib |
| Tautan penawaran unik dengan masa berlaku | Wajib |
| Penguncian kurs pada penawaran | Wajib |
| Tombol WhatsApp dengan template | Wajib |
| Pengingat follow-up | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `leads` | id, branch_id, name, phone, country, locale, source, status, assigned_to, lost_reason |
| `lead_activities` | id, lead_id, user_id, type, note, created_at |
| `quotations` | id, branch_id, lead_id, package_id, token, currency, locked_rate, valid_until, status |
| `quotation_items` | id, quotation_id, description(JSON), qty, unit_price_minor, total_minor |

### Langkah

1. Migrasi dan model seluruh tabel.
2. Papan lead dengan filter status, sumber, penanggung jawab.
3. Formulir pencatatan lead manual.
4. Endpoint penerimaan lead dari formulir publik + proteksi spam.
5. Pencatatan riwayat interaksi.
6. Ubah status lead, **alasan wajib saat status kalah**.
7. Pembuatan penawaran dari paket, salin rincian harga sebagai **snapshot**.
8. Kunci kurs saat penawaran dibuat, simpan di `locked_rate`.
9. Token acak untuk tautan penawaran publik.
10. Halaman penawaran publik tanpa login, dalam bahasa tamu.
11. Kedaluwarsa otomatis lewat scheduled job.
12. Tombol WhatsApp dengan template sesuai bahasa lead.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `LeadCrudTest.php` | Lead tercatat dari formulir publik dan manual |
| `LeadStatusTest.php` | Status kalah tanpa alasan ditolak |
| `CreateQuotationTest.php` | Penawaran menyimpan snapshot, bukan referensi |
| `RateLockTest.php` | Perubahan kurs tidak mengubah penawaran lama |
| `ExpiryTest.php` | Penawaran lewat masa berlaku berstatus expired |
| `PublicLinkTest.php` | Token valid bisa diakses; token acak ditolak |

```bash
php artisan test --filter="Lead|Quotation"
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Kirim permintaan dari formulir publik | Lead muncul dengan sumber website |
| 2 | Ubah status ke kalah tanpa alasan | Ditolak |
| 3 | Buat penawaran dari satu paket | Rincian harga tersalin lengkap |
| 4 | Buka tautan penawaran di jendela penyamaran | Terbuka tanpa login, bahasa lead |
| 5 | Ubah kurs SAR, buka kembali tautan | **Harga tidak berubah** |
| 6 | Ubah masa berlaku ke kemarin, jalankan scheduler | Berstatus expired, tautan menolak |
| 7 | Tekan tombol WhatsApp pada lead Arab | Template berbahasa Arab |
| 8 | Ubah satu karakter token | Ditolak |

### Definition of Done

- [ ] Seluruh lead dari formulir publik tercatat otomatis
- [ ] Penawaran menyimpan snapshot harga dan kurs terkunci
- [ ] Tautan publik aman dan menghormati masa berlaku
- [ ] Alasan kalah wajib terisi

---

# M7 — Booking & Order

**Tujuan:** mengubah penawaran yang disetujui jadi pemesanan, mengelola siklus hidupnya, menyimpan data tamu secara aman.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Konversi penawaran jadi pemesanan | Wajib |
| State machine status pemesanan | Wajib |
| Data tamu multi-pax | Wajib |
| Unggah dokumen identitas terenkripsi | Wajib |
| Voucher dan itinerary untuk tamu | Wajib |
| Kalender keberangkatan | Wajib |
| Catatan khusus tamu | Wajib |
| Pembatalan beserta alasan | Wajib |
| Halaman status untuk tamu | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `bookings` | id, branch_id, quotation_id, code, status, departure_date, return_date, total_minor, currency |
| `booking_guests` | id, booking_id, name, passport_number(**encrypted**), passport_file, nationality, is_lead_guest |
| `booking_notes` | id, booking_id, user_id, note, created_at |
| `booking_status_histories` | id, booking_id, from_status, to_status, user_id, reason, created_at |

### State machine

| Dari | Ke | Syarat |
|---|---|---|
| `draft` | `quoted` | Penawaran terkirim |
| `quoted` | `confirmed` | Tamu menyetujui |
| `quoted` | `expired` | Melewati masa berlaku |
| `confirmed` | `partially_paid` | DP terverifikasi |
| `partially_paid` | `paid` | Pelunasan terverifikasi |
| `paid` | `in_progress` | Tanggal keberangkatan tiba |
| `in_progress` | `completed` | Tanggal kembali terlewati |
| `*` (kecuali completed) | `cancelled` | Alasan wajib |

> **WAJIB:** setiap perpindahan menghasilkan satu baris `booking_status_histories` dan satu entri activity log. Perpindahan tidak terdaftar harus **melempar exception**, bukan diabaikan diam-diam.

### Langkah

1. Migrasi dan model seluruh tabel.
2. Kelas `BookingStateMachine` berisi seluruh transisi yang diizinkan.
3. **Unit test state machine lebih dulu**, termasuk seluruh transisi terlarang.
4. Konversi penawaran jadi pemesanan dengan kode unik.
5. Pengelolaan data tamu multi-pax.
6. Enkripsi nomor paspor dengan cast terenkripsi Laravel.
7. Berkas paspor di disk privat, akses hanya lewat **signed URL berbatas waktu**.
8. Catat setiap akses dokumen identitas di activity log.
9. **Blokir pengunduhan massal** dokumen secara teknis.
10. Kalender keberangkatan dan kedatangan.
11. Voucher dan itinerary lewat queue.
12. Pembatalan dengan alasan wajib.
13. Scheduled job untuk transisi otomatis berbasis tanggal.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `BookingStateMachineTest.php` | Seluruh transisi sah berhasil; terlarang melempar exception |
| `ConvertQuotationTest.php` | Menyalin data penawaran; kode unik tidak bentrok |
| `GuestDocumentTest.php` | Nomor paspor tersimpan terenkripsi |
| `DocumentAccessTest.php` | Akses tanpa signed URL ditolak; tiap akses tercatat |
| `CancellationTest.php` | Pembatalan tanpa alasan ditolak |
| `StatusHistoryTest.php` | Tiap perubahan status menghasilkan satu baris riwayat |

```bash
php artisan test --filter=Booking
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Setujui satu penawaran | Pemesanan terbentuk, kode unik, status confirmed |
| 2 | Tambah 3 data tamu + nomor paspor | **Periksa di database: nomor terenkripsi** |
| 3 | Unggah paspor, salin URL berkas | URL tanpa tanda tangan ditolak |
| 4 | Buka paspor lewat antarmuka, cek activity log | Akses tercatat dengan nama dan waktu |
| 5 | Ubah status confirmed → completed langsung | Ditolak, bukan diabaikan |
| 6 | Batalkan tanpa alasan | Ditolak |
| 7 | Batalkan dengan alasan, buka riwayat | Seluruh perpindahan tercatat berurutan |
| 8 | Coba unduh seluruh dokumen sekaligus | Diblokir |
| 9 | Ubah tanggal berangkat ke kemarin, jalankan scheduler | Status pindah ke in_progress otomatis |

### Definition of Done

- [ ] Seluruh transisi hanya lewat state machine
- [ ] Nomor paspor terbukti terenkripsi di database
- [ ] Berkas identitas hanya lewat signed URL
- [ ] Setiap akses dokumen tercatat
- [ ] Pengunduhan massal terblokir

---

# M8 — Driver & Armada

**Tujuan:** mengelola roster driver dan kendaraan, menugaskannya dengan memperhatikan preferensi tamu dan ketersediaan.

> **Cabang:** modul utama Bali. Kebutuhan Jakarta belum terkonfirmasi (asumsi V6, V7).

> **VALIDASI DIPERLUKAN.** Preferensi gender driver dijadikan fitur unggulan di materi penjualan. Jika permintaan driver perempuan ternyata jarang atau pasokannya tidak ada, prioritas fitur ini perlu diturunkan. **Konfirmasi asumsi V7 sebelum modul ini dimulai.**

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Roster driver dan kendaraan | Wajib |
| Preferensi gender driver pada pemesanan | Wajib |
| Saran driver otomatis sesuai preferensi dan ketersediaan | Wajib |
| Deteksi bentrok jadwal | Wajib |
| Kalender penugasan harian | Wajib |
| Cetak surat tugas | Sebaiknya |
| Catatan bahasa yang dikuasai driver | Sebaiknya |
| Perhitungan fee driver | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `drivers` | id, branch_id, name, gender, phone, languages(JSON), is_active |
| `vehicles` | id, branch_id, plate, type, capacity, is_active |
| `driver_assignments` | id, booking_id, driver_id, vehicle_id, date_from, date_to, status |

### Langkah

1. Migrasi dan model seluruh tabel.
2. Tambah kolom preferensi gender driver pada `bookings`.
3. Pengelolaan roster driver dan kendaraan.
4. Kelas `AssignmentService`: saring driver berdasarkan preferensi, cabang, ketersediaan tanggal.
5. **Unit test `AssignmentService` lebih dulu**, termasuk kasus tidak ada driver cocok.
6. Deteksi bentrok: satu driver tidak boleh punya dua penugasan beririsan.
7. Antarmuka penugasan dengan daftar saran + alasannya.
8. Kalender penugasan harian per cabang.
9. Kasus tidak ada driver sesuai preferensi: **tampilkan peringatan jelas**, jangan diam-diam menugaskan yang tidak sesuai.
10. Cetak surat tugas.
11. Catat penugasan dan perubahannya di activity log.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `AssignmentServiceTest.php` | Driver perempuan terpilih saat preferensi perempuan; kosong bila tidak ada |
| `ConflictDetectionTest.php` | Penugasan tanggal beririsan ditolak |
| `DriverCrudTest.php` | Driver nonaktif tidak muncul di saran |
| `AssignmentPermissionTest.php` | Finance Admin tidak bisa menugaskan |
| `BranchIsolationTest.php` | Driver Bali tidak muncul untuk pemesanan Jakarta |

```bash
php artisan test --filter="Fleet|Assignment"
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Pemesanan dengan preferensi driver perempuan | Hanya driver perempuan di daftar saran |
| 2 | Nonaktifkan semua driver perempuan, ulangi | Peringatan jelas, tidak ada penugasan salah |
| 3 | Tugaskan driver tanggal 1–5 | Tersimpan |
| 4 | Tugaskan driver sama tanggal 3–7 | Ditolak, pesan menyebut bentroknya |
| 5 | Buka kalender penugasan | Seluruh penugasan di tanggal benar |
| 6 | Finance Admin coba menugaskan | Ditolak |
| 7 | Buka pemesanan Jakarta | Driver Bali tidak muncul |
| 8 | Cetak surat tugas | Berisi driver, kendaraan, tamu, jadwal |

### Definition of Done

- [ ] Preferensi gender dihormati, tidak pernah dilanggar diam-diam
- [ ] Bentrok jadwal terdeteksi dan ditolak
- [ ] Driver terisolasi per cabang
- [ ] Kasus tidak ada driver cocok ditangani eksplisit

---

# M9 — Finance & Pembayaran

**Tujuan:** mencatat arus uang, memverifikasi pembayaran, merekonsiliasi dengan pemesanan, menampilkan margin sesungguhnya setelah biaya.

> **Cabang:** penekanan utama dari Jakarta.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Pencatatan pembayaran DP dan pelunasan | Wajib |
| Unggah dan verifikasi bukti transfer | Wajib |
| Rekonsiliasi uang masuk dengan pemesanan | Wajib |
| Daftar piutang | Wajib |
| Pencatatan pembayaran ke vendor | Wajib |
| Laporan margin setelah biaya | Wajib |
| Multi-mata-uang pada catatan pembayaran | Wajib |
| Pencatatan refund dan pembatalan | Wajib |
| Invoice dan kwitansi tiga bahasa | Wajib |
| Interface `PaymentProvider` siap pakai | Wajib |
| Ekspor untuk software akuntansi | Sebaiknya |

### Struktur data

| Tabel | Kolom penting |
|---|---|
| `payment_intents` | id, booking_id, channel, amount_minor, currency, fx_rate, status |
| `payments` | id, payment_intent_id, booking_id, type, amount_minor, currency, fx_rate, idr_equivalent_minor, proof_file, verified_by, verified_at |
| `vendor_payments` | id, booking_id, partner_id, amount_minor, currency, paid_at |
| `refunds` | id, booking_id, amount_minor, currency, reason, processed_by |

### Arsitektur penyedia pembayaran

```
BookingService
     |
     v
PaymentProvider (interface)
  |- ManualTransferProvider     Fase 1
  |- XenditProvider             Fase 2
  |- MiddleEastProvider         Fase 3
```

- Fase 1 **hanya** mengimplementasikan `ManualTransferProvider`. Interface tetap dibuat agar Fase 2 tidak membongkar ulang.
- Data kartu **tidak pernah** menyentuh server. Gunakan hosted page atau tokenisasi.
- Setiap pembayaran menyimpan mata uang asli, jumlah asli, kurs, dan ekuivalen rupiah.

> **MARGIN SESUNGGUHNYA.** Laporan margin wajib mengurangi biaya kanal pembayaran. Tanpa ini, transaksi kartu internasional tampak untung padahal ±5–6% sudah hilang.

### Langkah

1. Migrasi dan model seluruh tabel.
2. Interface `PaymentProvider` + `ManualTransferProvider`.
3. Pencatatan pembayaran dengan tipe DP atau pelunasan.
4. Unggah bukti transfer ke disk privat.
5. Alur verifikasi khusus peran Finance, catat verifikator dan waktunya.
6. Hubungkan verifikasi dengan state machine pemesanan.
7. Ekuivalen rupiah dengan kurs saat pembayaran.
8. Halaman rekonsiliasi: total tagihan, terbayar, sisa.
9. Daftar piutang dengan filter jatuh tempo.
10. Pencatatan pembayaran ke vendor.
11. Laporan margin dengan pengurangan biaya kanal.
12. Refund dengan alasan wajib.
13. Invoice dan kwitansi tiga bahasa lewat queue.
14. **Pembatasan: pembuat pemesanan tidak bisa memverifikasi pembayarannya sendiri.**

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `PaymentProviderTest.php` | `ManualTransferProvider` memenuhi kontrak interface |
| `PaymentVerificationTest.php` | Hanya Finance bisa verifikasi; CS ditolak |
| `SelfApprovalTest.php` | Pembuat pemesanan tidak bisa verifikasi pembayaran sama |
| `ReconciliationTest.php` | Total terbayar dan sisa benar untuk pembayaran bertahap |
| `MultiCurrencyPaymentTest.php` | Pembayaran SAR tersimpan dengan kurs dan ekuivalen IDR |
| `MarginReportTest.php` | Margin berkurang sesuai biaya kanal |
| `RefundTest.php` | Refund tanpa alasan ditolak; status menyesuaikan |

```bash
php artisan test --filter=Finance
php artisan test --coverage --min=85 --filter=Finance
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Catat DP 30%, verifikasi sebagai Finance | Status pindah ke partially_paid |
| 2 | CS coba verifikasi pembayaran | Ditolak |
| 3 | Finance verifikasi pemesanan buatannya sendiri | Ditolak |
| 4 | Catat pelunasan | Status paid, sisa nol |
| 5 | Catat pembayaran dalam SAR | Kurs dan ekuivalen IDR benar |
| 6 | Buka laporan margin pemesanan kartu internasional | Margin lebih kecil dibanding transfer |
| 7 | Buka daftar piutang | Hanya yang punya sisa tagihan |
| 8 | Catat refund tanpa alasan | Ditolak |
| 9 | Cetak invoice bahasa Arab | Tata letak benar, angka sesuai konvensi |
| 10 | Akses bukti transfer lewat URL langsung | Ditolak |

### Definition of Done

- [ ] `PaymentProvider` terdefinisi, `ManualTransferProvider` berfungsi penuh
- [ ] Segregasi tugas ditegakkan, termasuk larangan menyetujui sendiri
- [ ] Rekonsiliasi akurat untuk pembayaran bertahap dan multi-mata-uang
- [ ] Laporan margin memperhitungkan biaya kanal
- [ ] Cakupan pengujian minimal 85%

---

# M10 — Storefront Publik

**Tujuan:** halaman publik yang dilihat tamu. Fokus pada kepercayaan dan kemudahan, bukan kelengkapan fitur.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Landing page tiga bahasa dengan RTL | Wajib |
| Katalog paket | Wajib |
| Halaman detail paket dan galeri | Wajib |
| Pemilih mata uang | Wajib |
| Formulir permintaan jadi lead | Wajib |
| Tombol WhatsApp | Wajib |
| Halaman destinasi | Sebaiknya |
| Pencarian dan filter paket | Sebaiknya |

### Ketentuan desain

| Hindari | Gunakan |
|---|---|
| Hero gradien generik | Hero editorial, fotografi destinasi, palet Bagian 4 |
| Seluruh kartu seragam tanpa hierarki | Kartu paket utama `border-2 border-red-600`, pendukung `border-neutral-200` |
| Font bawaan dipakai polos | Pasangan font disengaja, fallback Arab terbaca |
| Foto stok tanpa konteks | Fotografi destinasi nyata dengan sensitivitas budaya |
| Emoji sebagai ikon | SVG dengan stroke dan ukuran konsisten |
| `rounded-2xl` di mana-mana | Radius 4–8px sesuai Bagian 4.4 |

> **SENSITIVITAS BUDAYA.** Audiens adalah keluarga Timur Tengah. Hindari gambar berpakaian terbuka, alkohol, dan simbol keagamaan non-Islam. Satu gambar keliru cukup untuk menghilangkan kepercayaan di halaman pertama.

### Langkah

1. Layout publik terpisah dari layout admin.
2. Landing page: hero, paket unggulan, keunggulan, preferensi gender driver, destinasi, cara memesan, formulir.
3. Katalog paket dari M5, hanya yang berstatus terbit.
4. Detail paket + galeri + rincian inklusi.
5. Pemilih mata uang yang mengubah harga tanpa muat ulang.
6. Formulir permintaan → lead, dengan proteksi spam dan pembatasan laju.
7. Validasi klien dengan Alpine; validasi server **tetap wajib**.
8. Lazy loading gambar dengan rasio aspek eksplisit.
9. Uji seluruh halaman di tiga bahasa dan lebar ponsel.
10. Meta tag dasar dan sitemap.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `LandingPageTest.php` | Merespons 200 di tiga locale |
| `PackageCatalogTest.php` | Hanya paket terbit tampil; nonaktif tidak bisa diakses langsung |
| `RequestFormTest.php` | Formulir valid membuat lead; invalid ditolak |
| `RateLimitTest.php` | Pengiriman berulang dibatasi |
| `CurrencySwitchTest.php` | Harga berubah sesuai mata uang |

```bash
php artisan test --filter=Public
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Landing page mode Arab | Seluruh bagian berbalik arah benar |
| 2 | Lebar ponsel | Tidak ada scroll horizontal |
| 3 | Ganti mata uang ke SAR | Harga berubah tanpa muat ulang |
| 4 | Kirim formulir | Lead muncul di admin, sumber website |
| 5 | Kirim 10x berturut | Dibatasi setelah beberapa kali |
| 6 | Buka paket nonaktif lewat URL | Ditolak |
| 7 | Periksa seluruh gambar | Tidak ada yang melanggar sensitivitas budaya |
| 8 | Navigasi hanya dengan keyboard | Seluruh elemen terjangkau, fokus terlihat |
| 9 | Hitung tombol merah per layar | Maksimal satu |

### Definition of Done

- [ ] Seluruh halaman rapi di tiga bahasa termasuk RTL
- [ ] Formulir menghasilkan lead dan terlindung spam
- [ ] Pemilih mata uang bekerja tanpa muat ulang
- [ ] Seluruh gambar lolos pemeriksaan sensitivitas budaya
- [ ] Navigasi keyboard dan kontras memenuhi WCAG 2.1 AA
- [ ] Palet sesuai Bagian 4, tidak ada warna di luar token

---

# M11 — Laporan & Dashboard

**Tujuan:** gambaran operasional dan keuangan yang dapat ditindaklanjuti, per cabang dan gabungan.

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Ringkasan pemesanan dan pendapatan | Wajib |
| Margin per produk dan periode | Wajib |
| Konversi lead jadi pemesanan | Wajib |
| Laporan per cabang dan gabungan | Wajib |
| Ekspor Excel | Wajib |
| Performa mitra hotel | Sebaiknya |
| Utilisasi driver dan kendaraan | Sebaiknya |

### Langkah

1. Rancang kueri agregat, pastikan kolom yang difilter terindeks.
2. Dashboard dengan kartu ringkasan: pemesanan, pendapatan, margin, lead.
3. Laporan margin per produk dengan pengurangan biaya kanal.
4. Laporan konversi lead + rincian alasan kalah.
5. Filter periode, cabang, tipe produk.
6. Pembatasan: hanya peran berwenang melihat margin.
7. Ekspor Excel lewat queue.
8. Cache pada kueri berat dengan masa berlaku singkat.
9. Uji performa dengan data contoh besar.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `DashboardTest.php` | Angka ringkasan sesuai data contoh |
| `MarginReportTest.php` | Margin memperhitungkan biaya kanal |
| `BranchFilterTest.php` | Laporan cabang hanya memuat data cabang itu |
| `ReportPermissionTest.php` | CS Admin tidak lihat laporan margin penuh |
| `ExportTest.php` | Ekspor lewat queue, menghasilkan berkas |

```bash
php artisan test --filter=Report
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | Buat 10 pemesanan contoh, buka dashboard | Angka cocok dengan hitungan manual |
| 2 | Filter periode ke bulan lalu | Hanya data periode itu |
| 3 | Bandingkan laporan Bali dan Jakarta | Tidak ada data bocor antar cabang |
| 4 | CS Admin buka laporan margin | Hanya sebagian data sesuai izin |
| 5 | Ekspor ke Excel | Isi cocok dengan tampilan layar |
| 6 | Muat dashboard dengan data besar | Di bawah 3 detik |

### Definition of Done

- [ ] Seluruh angka cocok dengan hitungan manual
- [ ] Margin memperhitungkan biaya kanal
- [ ] Pemisahan cabang terjaga
- [ ] Ekspor lewat queue
- [ ] Dashboard responsif pada volume besar

---

# M12 — Audit, Keamanan & Hardening

**Tujuan:** memastikan sistem memenuhi standar keamanan dan kepatuhan sebelum dipakai dengan data nyata. **Tidak opsional dan tidak boleh dipadatkan.**

### Ruang lingkup

| Fitur | Prioritas |
|---|---|
| Kelengkapan activity log di seluruh aksi sensitif | Wajib |
| Halaman penelusuran activity log | Wajib |
| Enkripsi data sensitif menyeluruh | Wajib |
| Signed URL untuk seluruh berkas privat | Wajib |
| Pembatasan laju pada autentikasi | Wajib |
| Kebijakan retensi dan penghapusan data | Wajib |
| Backup otomatis dan uji pemulihan | Wajib |
| 2FA untuk admin | Sebaiknya |

### Langkah

1. Audit seluruh modul, pastikan tiap aksi sensitif tercatat.
2. Halaman penelusuran activity log dengan filter aktor, modul, periode.
3. Periksa seluruh kolom sensitif sudah terenkripsi.
4. Periksa seluruh berkas privat hanya lewat signed URL.
5. Pembatasan laju pada login dan formulir publik.
6. Kebijakan retensi: penghapusan otomatis dokumen identitas setelah periode disepakati.
7. Fitur penghapusan data atas permintaan tamu (UU PDP).
8. Backup harian otomatis.
9. **Uji pemulihan dari backup ke lingkungan terpisah.**
10. Pemeriksaan terhadap OWASP Top 10.
11. `composer audit` dan `npm audit`, tangani temuan kritis.
12. Dokumen prosedur tanggap insiden.

### Pengujian otomatis

| Berkas | Kasus |
|---|---|
| `ActivityLogCoverageTest.php` | Tiap aksi sensitif menghasilkan entri log |
| `EncryptionTest.php` | Kolom sensitif tidak terbaca sebagai teks biasa |
| `SignedUrlTest.php` | Berkas privat tanpa tanda tangan selalu ditolak |
| `SecurityRateLimitTest.php` | Login gagal berulang dibatasi |
| `AuthorizationSweepTest.php` | Seluruh rute admin menolak pengguna tanpa izin |
| `DataRetentionTest.php` | Dokumen lewat masa retensi terhapus |

```bash
php artisan test --filter=Security
composer audit
npm audit
```

### Pengujian manual

| # | Langkah | Hasil yang diharapkan |
|---|---|---|
| 1 | 10 aksi berbeda di berbagai modul, buka activity log | Seluruhnya tercatat lengkap |
| 2 | Buka database langsung, periksa nomor paspor | Tidak terbaca sebagai teks biasa |
| 3 | Akses seluruh berkas privat lewat URL langsung | Seluruhnya ditolak |
| 4 | Login salah 10x | Dibatasi setelah beberapa percobaan |
| 5 | Telusuri seluruh rute admin tanpa izin | Seluruhnya 403 |
| 6 | Pulihkan backup ke lingkungan terpisah | Data utuh, aplikasi berjalan |
| 7 | Jalankan permintaan penghapusan data satu tamu | Terhapus dan tercatat |

### Definition of Done

- [ ] Seluruh aksi sensitif tercatat tanpa celah
- [ ] Data sensitif terenkripsi, terverifikasi langsung di database
- [ ] Seluruh berkas privat terlindungi signed URL
- [ ] Uji pemulihan backup berhasil
- [ ] Tidak ada temuan kritis dari audit dependensi
- [ ] Prosedur tanggap insiden terdokumentasi

---

# 5. Strategi Pengujian

## 5.1 Tingkatan

| Tingkat | Cakupan | Kapan |
|---|---|---|
| Unit | Money, PricingEngine, StateMachine, AssignmentService | Tiap menyimpan berkas |
| Feature | Alur lengkap dengan database | Sebelum commit |
| Livewire | Interaksi komponen | Sebelum commit |
| Authorization | Seluruh pembatasan peran dan cabang | Sebelum commit |
| Manual | Prosedur tiap modul | Sebelum modul selesai |
| Regresi | Seluruh test suite | Sebelum rilis |
| Penerimaan | Bersama pengguna Indogate | Sebelum go-live |

## 5.2 Target cakupan

| Area | Minimum |
|---|---|
| PricingEngine dan Money | 90% |
| BookingStateMachine | 100% transisi |
| Modul Finance | 85% |
| Modul lainnya | 70% |
| Kode antarmuka murni | Tidak ditargetkan |

## 5.3 Urutan tiap modul

1. Tulis unit test logika inti **sebelum** antarmuka dibangun.
2. Bangun modul hingga seluruh unit test lulus.
3. Tulis feature test alur utama.
4. Tulis authorization test tiap peran yang terlibat.
5. Jalankan seluruh test suite — pastikan tidak ada modul lain rusak.
6. Jalankan Pint.
7. Serahkan prosedur uji manual **kepada orang lain**.
8. Perbaiki temuan, ulangi dari langkah 5.
9. Isi Definition of Done.

> **Aturan:** uji manual tidak boleh dijalankan oleh penulis kodenya sendiri. Penulis kode menguji sesuai cara yang ia bayangkan, bukan cara pengguna sebenarnya.

## 5.4 Uji penerimaan bersama klien

Sebelum go-live, data nyata terbatas, dijalankan staf Indogate sendiri.

| Skenario | Peran penguji |
|---|---|
| Menyusun paket 7 hari 2 hotel dari nol | CS Admin |
| Mengubah margin musim peak, periksa dampaknya | Super Admin |
| Menerima lead dari website hingga menerbitkan penawaran | CS Admin |
| Memverifikasi DP dan pelunasan mata uang berbeda | Finance Admin |
| Menugaskan driver perempuan | CS Admin |
| Membuka storefront bahasa Arab di ponsel | Siapa pun |
| Menutup laporan bulanan dan mengekspornya | Finance Admin |

---

# 6. Asumsi yang Belum Divalidasi

Modul yang bergantung pada butir ini **tidak boleh dimulai** sebelum jawabannya diperoleh.

| ID | Asumsi | Modul terdampak | Jawaban |
|---|---|---|---|
| V1 | Mayoritas transaksi berupa paket, bukan komponen satuan | M5 | `[ISI]` |
| V2 | Penjualan B2C langsung, bukan lewat agen | M14 | `[ISI]` |
| V3 | Margin ditentukan dengan persentase baku | M4 | `[ISI]` |
| V4 | Mata uang utama penawaran adalah SAR | M4 | `[ISI]` |
| V5 | Belum ada entitas atau rekening di luar negeri | M9, M13 | `[ISI]` |
| V6 | Driver merupakan mitra, bukan karyawan tetap | M8 | `[ISI]` |
| V7 | Permintaan driver perempuan cukup sering dan pasokan tersedia | M8 | `[ISI]` |
| V8 | Tidak ada data historis besar yang wajib dimigrasi | Lingkup keseluruhan | `[ISI]` |
| V9 | Sistem lama dimatikan, bukan dijalankan paralel | Strategi cutover | `[ISI]` |
| V10 | Sistem untuk internal Indogate saja, bukan multi-tenant | Arsitektur dasar | `[ISI]` |
| V11 | Tersedia penutur asli bahasa Arab untuk konten | M2, M10 | `[ISI]` |
| V12 | Kebutuhan Bali dan Jakarta dapat dilayani satu sistem | M1 dan seluruhnya | `[ISI]` |
| V13 | Tawaran vendor payment gateway dapat ditolak atau dinegosiasi | M13 | `[ISI]` |
| V14 | Merah–biru adalah warna brand yang disetujui klien | Bagian 4, M2, M10 | `[ISI]` |

## 6.1 Risiko utama

| Risiko | Mitigasi |
|---|---|
| Kebutuhan Bali dan Jakarta tidak dapat disatukan | Pemisahan cabang sejak M1; siapkan opsi dua konfigurasi |
| Package Builder lambat karena sifat Livewire | Alpine untuk state lokal; ukur performa sejak awal M5 |
| Tidak ada entitas di Saudi | Arsitektur `PaymentProvider` agnostik sejak M9 |
| Konten Arab tidak ditangani penutur asli | Anggarkan penerjemah profesional sejak awal |
| Staf tetap memakai WhatsApp | Libatkan pengguna harian sejak uji penerimaan; sediakan jalur masuk dari WhatsApp |
| Data identitas lama sudah tersebar | Masukkan pembersihan data lama ke lingkup M12 |
| Klien menolak palet merah–biru | Token terpusat — pergantian palet hanya mengubah satu berkas config |

## 6.2 Riwayat revisi

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0 | `[TANGGAL]` | Versi awal |
| 2.0 | `[TANGGAL]` | Package Builder, multi-currency, modul lead |
| 3.0 | `[TANGGAL]` | Stack Livewire, langkah dan pengujian per modul, multi-cabang, biaya kanal |
| 3.1 | `[TANGGAL]` | Design system dan color palette (Bagian 4); format Markdown |
| `[  ]` | `[TANGGAL]` | `[PERUBAHAN]` |

## 6.3 Persetujuan

| Peran | Nama | Tanda tangan | Tanggal |
|---|---|---|---|
| PIC Indogate | `[NAMA]` | | |
| Project Lead Raynad | `[NAMA]` | | |
| Tech Lead | `[NAMA]` | | |
