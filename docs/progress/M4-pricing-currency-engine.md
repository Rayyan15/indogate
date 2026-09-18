# M4 — Pricing & Currency Engine

**Status:** SELESAI · **Tanggal:** 2026-09-18 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M4

## Keputusan arsitektur (final, berlaku buat modul selanjutnya)

1. **Konflik nama tabel**: PRD minta `pricing_rules`, tapi nama itu udah kepake tabel MVP lama. Skema PRD dinamain **`margin_rules`**, tabel lama (`admin/pricing`, `PricingRuleController`, route `admin.pricing.*`) dibiarkan gak diapa-apain dan tetap jalan berdampingan.
2. **`product_type`** di `margin_rules` reuse `App\Enums\InventoryItemType` (room/vehicle/ticket/activity) dari M3.
3. **Namespace domain**: `App\Domain\Pricing\*` (Money, Override, PricingRequest, PricingLineItem, PricingBreakdown, RuleResolver, Converter, PricingEngine, Exceptions, Rules) + `App\Domain\Pricing\Models\*` (Currency, ExchangeRate, Season, MarginRule, PaymentChannelCost).
4. **`App\Support\Localization\Money` (M2)** tetap terpisah — cuma formatter tampilan, bukan value object M4.
5. **`exchange_rates` insert-only** — gak pernah di-update. "Kurs saat ini" = baris `effective_from <= now()` terbaru (`ExchangeRate::currentFor()`).
6. **`Override`** (value object) wajib `reason` non-kosong, divalidasi di constructor — lempar `InvalidArgumentException` kalau kosong.
7. **Log override**: `activity('pricing')->withProperties([...])->log(...)` **tanpa subject model** (belum ada tabel `quotations`, baru M6). Properties: `sell_idr_minor`, `override_amount_minor`, `override_currency`, `reason`.
8. **Permission**: `pricing.manage` dipakai ulang buat SEMUA layar M4 (Currencies, Exchange Rates, Seasons, Margin Rules, Payment Channel Costs, Simulator) — permission `currency.manage` yang ada di matriks PRD lama sengaja gak dipakai di sini, sesuai keputusan owner sebelumnya.
9. **Season overlap**: ditolak per cabang (bukan per product_type), pola sama `RateDoesNotOverlap` M3 — lihat `SeasonDoesNotOverlap`.
10. **Uang**: `margin_percent` dan `percent_fee` disimpan sebagai **basis poin integer** (2500 = 25.00%), cocok langsung dipakai `Money::multiplyByBasisPoints()`. Tidak ada float di jalur manapun.
11. **`MoneyCast`**: cast parameterized `MoneyCast::class.':currency_column'` buat kolom model yang mau expose `Money` langsung — dipakai di `PaymentChannelCost::flat_fee_minor`.
12. **Livewire tidak bisa serialize value object domain langsung** (pelajaran dari bug di sesi ini) — `PricingSimulator::$result` disimpan sebagai `array` hasil flatten dari `PricingBreakdown`, bukan objeknya. Kalau bikin Livewire component lain yang expose hasil `PricingEngine`, ikuti pola ini.

## Yang sudah jadi (teruji, jalan di browser)

- **Domain core**: `Money`, `Override`, `PricingLineItem`, `PricingRequest`, `PricingBreakdown`, `RuleResolver`, `Converter`, `PricingEngine`, 3 exception class.
- **5 migrasi** + 2 enum (`SeasonType`, `PaymentChannel`) + 5 model Eloquent (semua `LogsActivity`, `Season`/`MarginRule` pakai `BelongsToBranch`).
- **`SeasonDoesNotOverlap`** validation rule + `SeasonPolicy`, `MarginRulePolicy` (terdaftar di `AppServiceProvider`).
- **UI Livewire lengkap**: Currency, ExchangeRate (insert-only, gak ada edit), Season, MarginRule (dengan preview dampak margin), PaymentChannelCost — semua List+Form terpisah pola `PartnerList`/`PartnerForm`. Plus **Pricing Simulator** buat uji manual override+reason+activity-log.
- **Routes**: `admin.pricing-engine.*` (prefix `pricing-engine`, terpisah dari `admin.pricing.*` MVP lama), sidebar link baru, tab navigation antar 6 layar.
- **Lang**: `lang/{en,id,ar}/pricing.php` lengkap, `nav.pricing_engine` di 3 locale.
- **`PricingSeeder`**: currency IDR/USD/SAR, kurs awal USD/SAR, 3 season contoh per cabang, margin rule contoh tiap product_type, channel cost bank_transfer/international_card. Terpanggil dari `DatabaseSeeder`.

## Hasil pengujian

- **98 test PASS** (93 lama + 5 baru: `RuleResolverTest` x3, `ConverterTest` x4, `ChannelCostTest` x1, `PricingEngineTest` x5, `RateManagementTest` x3, `SeasonOverlapTest` x3, `PricingEnginePermissionTest` x2, `PricingSimulatorTest` x3 — total 24 test case baru M4), 0 regresi.
- Pint bersih (`./vendor/bin/pint --test` passed).
- **Coverage `--min=90` BELUM bisa dijalankan** — driver Xdebug/PCOV belum terinstal di environment ini (`php artisan test --coverage` gagal dengan "Code coverage driver not available"). DoD baris ini belum terbukti secara numerik, meski `PricingEngine` punya 5 test yang cover peak/high/low/margin-nol/kosong/no-rule/override.
- **Dites di browser sungguhan** (`php artisan serve` + Claude in Chrome, login `admin@indogate.com`): semua 6 layar render dengan data seeded, form create/edit modal buka-tutup normal, season overlap ditolak dengan pesan lang yang benar, Simulator golden path (margin 25% High season → 1.000.000 jadi 1.250.000) dan edge case (tanggal tanpa musim/rule → pesan error tampil, bukan crash) berfungsi, override tanpa alasan ditolak lalu dengan alasan berhasil + tercatat di `activity_log`.
- **Bug ditemukan & diperbaiki selama browser test**: Livewire melempar `PropertyNotFoundException`/gagal serialize saat `PricingSimulator::$result` bertipe `PricingBreakdown` (value object biasa, bukan Livewire-aware). Diperbaiki dengan flatten ke `array` — lihat keputusan #12.

## Perbaikan tambahan (browser test menyeluruh, sesi lanjutan 2026-09-18)

- **Modal edit Mata Uang** dulu selalu judul "Mata Uang Baru" — sekarang beda judul create (`form_title`) vs edit (`form_title_edit`).
- **Celah data**: `MarginRuleForm` dulu bisa nyimpen duplikat `(branch_id, product_type, season_type)` → `RuleResolver` jadi ambigu pilih baris mana. Ditambah cek duplikat sebelum save, pesan error `pricing.margin_rule.duplicate_error`. Test: `MarginRuleDuplicateTest` (2 case).
- **Format tampilan Simulator**: dulu nampilin minor unit mentah (`8924 USD`, `1410000`). Sekarang reuse `App\Support\Localization\Money::format()` (formatter M2) buat tampilan utama (`US$79,11`, `Rp 1.250.000,00`), angka minor unit tetap ditampilkan kecil di bawahnya buat staf finance yang butuh angka pasti.
- **Tab navigasi** (`_tabs.blade.php`) ditambah `overflow-x-auto` + `shrink-0 whitespace-nowrap` biar gak overflow di layar sempit.
- Catatan: tool resize browser gak mau nurut di sesi ini (window nolak diperkecil ke ukuran HP beneran), jadi verifikasi mobile murni dari audit class Tailwind (table pakai `overflow-x-auto`, grid Simulator `grid-cols-1 lg:grid-cols-2` stack di mobile), bukan screenshot asli viewport 390px.
- Test setelah perbaikan: **100 PASS** (98→100), Pint bersih, 0 regresi.

## Demo seeder (data realistis per alur, opsional — bukan bagian `DatabaseSeeder` default)

Dibuat 3 seeder tambahan biar tiap alur M1/M3/M4 kelihatan kasus nyata pas dipakai, bukan cuma data minimal:

- **`RoleDemoSeeder`** (M1): CS Admin + Finance Admin di kedua cabang (Bali & Jakarta), 1 akun `is_active=false` (bukti login beneran diblokir — lihat `FortifyServiceProvider::authenticateUsing`), 2 customer tambahan.
- **`CatalogDemoSeeder`** (M3): 3 mitra/cabang (hotel, vila, vendor kendaraan), 7 item/cabang (semua `InventoryItemType`), tiap item punya **riwayat harga 3 periode** (2025 lampau/2026 aktif/2027 mendatang) bukan cuma 1 baris, 1 `BlackoutDate` riil, sample `ItemMedia`.
- **`PricingDemoSeeder`** (M4): 5 currency (1 nonaktif — KRW, contoh toggle), riwayat kurs 3 currency, kalender musim **beda bentuk per cabang** (Bali destinasi liburan long-peak, Jakarta MICE nyaris gak ada peak), margin **beda per cabang** (Bali lebih tebal, Jakarta lebih tipis) + 1 rule nonaktif contoh, biaya kanal, 2 riwayat override manual (activity log gak kosong pas pertama dibuka).

Cara pakai: `php artisan migrate:fresh --seed` dulu (baseline), lanjut `php artisan db:seed --class=RoleDemoSeeder`, `--class=CatalogDemoSeeder`, `--class=PricingDemoSeeder`. Semua **idempoten** — aman dijalanin ulang berkali-kali, gak dobel data.

**Bug ditemukan pas nulis seeder**: DB dev proyek ini **SQLite**. Kolom bertipe `date` cast Eloquent nyimpen string dengan suffix `00:00:00`, sementara `firstOrCreate` pakai key tanggal mentah (`'2025-01-01'`) gak match ke situ → tiap run bikin baris baru dobel terus (`Rate`, `BlackoutDate` di `CatalogDemoSeeder` kena ini). Diperbaiki pakai pola `whereDate()` buat cek exists dulu sebelum `create()`. **Catatan buat modul selanjutnya**: kalau bikin seeder yang `firstOrCreate`/`updateOrCreate` keyed oleh kolom bertipe `date`, jangan pakai key mentah — pakai `whereDate()` manual atau key by kolom lain yang unik (nama, id, dst).

## Di luar lingkup (sengaja belum digarap)

- Coverage numerik 90% — butuh Xdebug/PCOV terinstal dulu di environment, bukan kerjaan kode.
- Integrasi `PricingEngine` ke quotation nyata — nunggu M6 (`quotations` belum ada tabelnya).
- Multi-currency untuk `payment_channel_costs.flat_fee_minor` — saat ini diasumsikan selalu IDR karena `Money::add()` nolak beda currency; kalau nanti butuh channel cost non-IDR, `PricingEngine::channelCost()` perlu convert dulu.
