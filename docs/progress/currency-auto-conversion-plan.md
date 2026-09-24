# Rencana Konversi Mata Uang Otomatis (Real-time)

Status: **tahap 1-6 selesai 2026-09-24** (fx:fetch, source/pin, spread+pembulatan tampilan, activity log, pratinjau `<x-ui.money-preview>` di form rate inventori/channel cost/simulator, hotels.currency, daftar mata uang storefront dinamis). Belum: QA Chrome (tahap 7), FxRateSource interface (sengaja dilewati: satu vendor, YAGNI), grafik mini 30 hari (opsional), fetch hourly (menunggu keputusan owner) · Dibuat: 2026-09-23

## 1. Tujuan

Admin cukup mengisi harga dalam satu mata uang, misalnya **1.000 USD**. Sistem langsung menampilkan padanannya di semua mata uang aktif (IDR, EUR, AED, SAR, dan lainnya) saat itu juga, dan kurs diperbarui otomatis dari sumber resmi. Admin tidak lagi memasukkan kurs manual setiap hari.

Catatan istilah: "UAE" = mata uang **AED** (Dirham UEA). Kodenya sudah ada di tabel `currencies`.

## 2. Yang sudah ada (hasil audit kode)

| Bagian | Kondisi | Otomatis? |
|---|---|---|
| Tabel `currencies` | AED, EUR, IDR, KRW, SAR, USD dengan `decimal_places` (IDR/KRW 0, lainnya 2) | ✔ |
| Tabel `exchange_rates` | `currency`, `rate` (IDR per 1 unit), `effective_from`, `created_by`. Insert-only, jadi riwayat terjaga | — |
| `ExchangeRateForm` / `ExchangeRateList` | Admin **mengetik kurs secara manual** | ✘ manual |
| `ExchangeRate::currentFor()` | Mengambil kurs terbaru yang `effective_from <= now` | ✔ |
| `App\Domain\Finance\Fx` | `rate()` melempar exception kalau kurs kosong (tidak ada fallback 1.0), `toIdrMinor`, `fromIdrMinor`, `format` sadar `decimal_places` | ✔ |
| `PricingEngine::toIdr` | Rate inventori non-IDR (`rates.currency`) dikonversi ke IDR | ✔ |
| `Quotation.locked_rate` | Kurs dikunci saat quotation terbit; booking memakai kurs itu (`PackageBooking::lockedRate`) | ✔ |
| `StorefrontCurrency` | Hanya SAR/USD/IDR; ada **fallback hardcode** SAR 4.250 / USD 16.000 kalau kurs gagal | ⚠ |
| `PackageBuilder.display_currency` | Pratinjau harga paket dalam mata uang pilihan | ✔ |
| `hotels.base_price_per_night` | Kolom `numeric` tanpa mata uang (legacy MVP) | ✘ |
| Scheduler | Hanya `ExpireQuotations` dan `TransitionBookingStatuses`, belum ada pengambilan kurs | ✘ |

Kesimpulan: arsitekturnya sudah benar (IDR sebagai pivot, minor unit, kurs dikunci di quotation). Yang kurang ada tiga: **sumber kurs otomatis**, **pratinjau konversi di form admin**, dan pembersihan fallback hardcode.

## 3. Desain

### 3.1 Prinsip: IDR sebagai pivot
- Semua kurs disimpan sebagai **IDR per 1 unit** mata uang asing (desain yang sudah ada).
- Konversi X ke Y = X → IDR → Y memakai `Fx::toIdrMinor` lalu `Fx::fromIdrMinor`. Contoh: 1.000 USD → IDR → AED.
- Uang selalu **integer minor unit** mengikuti `decimal_places`. Float hanya dipakai untuk tampilan.

### 3.2 Sumber kurs otomatis
Kandidat (perlu dicek ulang sebelum dipilih):

| Sumber | AED/SAR | Biaya | Catatan |
|---|---|---|---|
| Frankfurter (data ECB) | ✘ tidak ada AED/SAR | gratis | Hanya ±30 mata uang ECB, jadi tidak cukup |
| exchangerate.host / Open Exchange Rates | ✔ | free tier terbatas / berbayar | Update per jam, perlu API key |
| Bank Indonesia (JISDOR / kurs transaksi BI) | USD ✔; AED/SAR ada di kurs transaksi | gratis | Resmi untuk IDR; update harian, bukan real-time |

**Rekomendasi:** API komersial (misalnya Open Exchange Rates) sebagai sumber utama per jam, ditambah JISDOR BI sebagai pembanding harian untuk USD/IDR.

Implementasi:
- Interface `FxRateSource` + satu kelas per vendor, supaya vendor bisa diganti.
- Command `fx:fetch` dijadwalkan `hourly()` di `bootstrap/app.php`. Command ini membuat baris baru di `exchange_rates` dengan `source` (`api:<vendor>` / `manual`) dan `created_by` = akun sistem.
- **Sanity guard:** kalau kurs baru berubah lebih dari X% (misalnya 5%) dari kurs sebelumnya, jangan dipakai otomatis. Tandai "perlu review" dan beri tahu Super Admin/Finance.
- **Override manual** tetap bisa lewat `ExchangeRateForm`. Kurs manual bisa di-"pin" sampai tanggal tertentu, dan selama itu fetch otomatis tidak menimpanya.
- **Staleness guard:** kalau kurs terakhir lebih tua dari 24 jam, tampilkan banner peringatan di admin. Sistem tetap memakai kurs terakhir yang valid, **tidak pernah 1.0 dan tidak pernah angka hardcode**.
- Hapus fallback hardcode di `StorefrontCurrency` dan ganti dengan kurs terakhir yang tersimpan. Ini perubahan logic saja, tampilan storefront tidak berubah.

### 3.3 Spread / buffer risiko kurs
- Kolom baru `currencies.spread_bps`, misalnya 150 = 1,5%.
- Kurs jual ke customer = kurs pasar × (1 + spread). Tujuannya melindungi margin dari fluktuasi antara quotation terbit dan dana diterima.
- Kurs pasar mentah tetap disimpan untuk laporan margin (`MarginReportService`).

### 3.4 "Real-time" di form admin
- Setiap input harga (rate inventori, flat fee channel, hotel, simulator) mendapat komponen `<x-ui.money-input>`. Isinya: pilihan mata uang + angka, dan di bawahnya **pratinjau langsung** di semua mata uang aktif.
- Kurs terbaru disisipkan ke halaman sebagai JSON. Perhitungan dilakukan Alpine di browser, jadi tanpa request per ketikan dan hasilnya instan.
- Yang disimpan tetap **mata uang asli + minor unit** (1.000 USD disimpan sebagai `USD 100000`), bukan hasil konversi. Konversi selalu dihitung saat dipakai, jadi tidak basi.
- Kolom legacy `hotels.base_price_per_night` perlu ditambah `currency` supaya ikut skema ini.

### 3.5 Pembulatan tampilan per mata uang
- Konfigurasi di `currencies`: `display_rounding`, misalnya IDR ke atas ke 1.000 terdekat, SAR/AED/USD/EUR ke 1 unit utuh.
- Pembulatan **hanya untuk harga jual yang ditampilkan** (storefront, quotation). Perhitungan internal dan laporan memakai nilai presisi.

### 3.6 Kurs dikunci pada dokumen yang sudah terbit (aturan tetap)
- Storefront dan pratinjau admin memakai kurs terbaru.
- **Quotation mengunci kurs saat terbit** (`locked_rate`, sudah ada). Booking dan pembayarannya memakai kurs itu.
- Alasannya: kalau harga di quotation yang sudah dikirim ke customer ikut berubah tiap jam, customer melihat angka berbeda dari yang disepakati, invoice tidak cocok dengan pembayaran, dan laporan margin kacau. Real-time berlaku untuk **harga baru**, bukan transaksi yang sudah berjalan.

### 3.7 Audit
- Setiap kurs baru (otomatis atau manual) dicatat di activity log `pricing`: sumber, kurs lama → baru, persentase perubahan, dan pembuatnya.
- Halaman Kurs menampilkan grafik mini riwayat 30 hari per mata uang dan badge sumber.

## 4. Tahapan kerja

| # | Pekerjaan | Estimasi |
|---|-----------|----------|
| 1 | Migration: `exchange_rates.source`, `pinned_until`; `currencies.spread_bps`, `display_rounding`; `hotels.currency` | 0,5 hari |
| 2 | `FxRateSource` + 1 adapter vendor + command `fx:fetch` hourly + sanity & staleness guard | 1 hari |
| 3 | Terapkan spread + pembulatan di `Fx` / `Converter`; hapus fallback hardcode `StorefrontCurrency` | 0,5 hari |
| 4 | `<x-ui.money-input>` dengan pratinjau Alpine; pasang di form rate inventori, channel cost, hotel, simulator | 1 hari |
| 5 | Halaman Kurs: badge sumber, pin manual, banner basi, riwayat | 0,5 hari |
| 6 | Tambah AED/EUR ke pilihan storefront (logic saja; UI disepakati dengan rekan tim) | 0,25 hari |
| 7 | QA Chrome (id/en/ar RTL) + naskah demo | 0,5 hari |

Total ± **4,25 hari kerja**.

## 5. Test otomatis (minimal)

- 1.000 USD → IDR → AED memberi hasil sesuai kurs, dengan minor unit dan `decimal_places` benar (IDR 0, AED 2).
- `fx:fetch` dengan API palsu menyimpan kurs baru; lonjakan lebih dari batas tidak dipakai otomatis.
- API gagal: sistem memakai kurs terakhir, tidak pernah 1.0 atau hardcode, dan banner basi muncul setelah 24 jam.
- Kurs manual yang di-pin tidak ditimpa fetch otomatis.
- Quotation yang sudah terbit tidak berubah nilainya setelah kurs baru masuk.
- Spread dan pembulatan hanya berlaku untuk harga jual tampilan, bukan laporan margin.

## 6. Risiko

- **Kuota/biaya API.** Fetch per jam = ±720 request/bulan, masih dalam free tier umumnya. Kalau vendor down, sistem memakai kurs terakhir.
- **Kurs salah dari vendor.** Dimitigasi sanity guard + review manual.
- **Harga storefront berubah-ubah tiap jam** bisa membingungkan customer. Mitigasi: pembulatan tampilan, atau perbarui kurs storefront cukup harian (lihat keputusan).
- **Data legacy** (`hotels.base_price_per_night` tanpa mata uang) perlu diasumsikan IDR saat migrasi.

## 7. Naskah demo presentasi (± 3 menit)

1. Admin membuka form rate item, pilih **USD**, ketik **1000**. Di bawahnya langsung tampil IDR, EUR, AED, dan SAR yang berubah setiap angka diketik.
2. Buka halaman Kurs: badge "Otomatis · 12 menit lalu", sumber API, riwayat 30 hari.
3. Tunjukkan override manual: pin kurs USD untuk promo, lalu fetch otomatis dilewati.
4. Buat quotation, lalu simulasikan kurs baru masuk. Quotation lama tetap di angka yang sama (kurs terkunci), sedangkan pratinjau paket baru memakai kurs terbaru.
5. Storefront: ganti mata uang IDR ↔ SAR ↔ USD, harga ikut kurs terbaru dengan pembulatan rapi.

## 8. Keputusan yang dibutuhkan dari owner

1. **Vendor sumber kurs:** API berbayar per jam (rekomendasi, mencakup AED/SAR) atau cukup kurs BI harian?
2. **Spread per mata uang:** berapa persen buffer risiko kurs (usulan 1–2%), dan apakah berbeda per mata uang?
3. **Frekuensi harga storefront:** ikut kurs per jam, atau dikunci harian supaya harga di mata customer stabil?
4. Mata uang yang tampil di storefront: tetap SAR/USD/IDR atau ditambah AED/EUR?
