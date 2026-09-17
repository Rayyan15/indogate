# M4 — Pricing & Currency Engine

**Status:** BELUM SELESAI — sesi berakhir di tengah jalan, cuma fondasi paling dasar yang jadi. Jangan diklaim modul selesai. · **Tanggal:** 2026-09-17 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M4

## Kenapa berhenti di sini

Limit sesi habis di tengah eksekusi. Yang dikerjakan cuma potongan paling aman/self-contained (gak nyentuh DB, gak mungkin break apa pun yang udah jalan), sisanya BELUM disentuh sama sekali. Rencana lengkap dan disetujui ada di plan file sesi ini — kalau hilang, poin-poin di bawah "Belum dikerjakan" cukup buat rekonstruksi dari PRD §M4 lagi.

## Keputusan arsitektur (sudah difinalkan, dikonfirmasi owner, TETAP BERLAKU buat lanjutan)

1. **Konflik nama tabel**: PRD minta tabel `pricing_rules`, tapi nama itu udah kepake tabel MVP lama (`service_type`, `season_start/end` tanggal literal, `markup_percent`, masih dipakai `admin/pricing` yang jalan). **Keputusan: tabel baru PRD dinamain `margin_rules`**, tabel lama dibiarkan gak diapa-apain. Sama persis pola M3 (skema baru dibangun sendiri, punya lama dibiarkan).
2. **`product_type`** di `margin_rules` pakai ulang `App\Enums\InventoryItemType` (room/vehicle/ticket/activity) dari M3, jangan bikin enum baru.
3. **Namespace domain**: `App\Domain\Pricing\*` buat value object/service murni (Money, RuleResolver, Converter, PricingEngine, PricingRequest, PricingBreakdown, Override), `App\Domain\Pricing\Models\*` buat Eloquent (Currency, ExchangeRate, Season, MarginRule, PaymentChannelCost).
4. **Tidak ada tabrakan** dengan `App\Support\Localization\Money` (M2) — itu cuma formatter tampilan, docblock-nya sendiri udah bilang bukan value object M4.
5. **Kurs (`exchange_rates`) insert-only** — gak pernah di-update, selalu row baru dengan `effective_from`. "Kurs saat ini" = baris terbaru yang `effective_from <= now()`.
6. **Override harga wajib disertai alasan** — divalidasi di level konstruktor value object `Override` (amount ada tapi reason kosong → langsung exception), bukan cuma validasi form.
7. **Log override**: karena belum ada tabel `quotations` (baru muncul M6), override di-log lewat `activity('pricing')->...->log(...)` TANPA subject model — titik sambung ke `quotations` didokumentasikan buat nanti, bukan dibikin tabel sendiri sekarang.
8. **Permission**: pakai ulang `pricing.manage` yang udah ada buat semua layar baru M4 (Currencies, Exchange Rates, Seasons, Margin Rules, Payment Channel Costs, Simulator) — gak bikin permission baru.
9. **Season overlap**: tolak overlap apa pun (bukan per-type) untuk cabang yang sama — pola sama kayak `RateDoesNotOverlap` M3.

## Yang sudah jadi (aman, teruji)

- **`App\Domain\Pricing\Money`** (`app/Domain/Pricing/Money.php`) — value object `amount_minor:int` + `currency:string`, immutable. `add()`/`subtract()` (nolak beda mata uang lewat `CurrencyMismatchException`), `multiplyByBasisPoints(int $bp)` — murni integer math (`intdiv`), **tidak ada float sama sekali**. Ini fondasi "dilarang float" PRD.
- **3 exception class** kosong tapi siap pakai: `CurrencyMismatchException`, `NoApplicableMarginRuleException`, `ExchangeRateNotFoundException` (`app/Domain/Pricing/Exceptions/`).
- **`tests/Unit/MoneyTest.php`** — 10 test, semua PASS: presisi tambah/kurang, tolak beda mata uang, basis-points math exact (contoh 25% dari 84.250.000 = 21.062.500 pas), pembulatan truncate konsisten (bukan round), zero(), normalisasi kode mata uang uppercase, equals(), isNegative().
- Direktori kosong sudah disiapkan (belum ada isi): `app/Domain/Pricing/Models/`, `app/Domain/Pricing/Exceptions/` (isi), `app/Domain/Pricing/Rules/`, `app/Casts/`, `app/Livewire/Admin/Pricing/`, `resources/views/livewire/admin/pricing/`, `resources/views/admin/pricing-engine/`.

## Belum dikerjakan sama sekali (jangan diasumsikan ada)

- **Migrasi** (5 tabel): `currencies`, `exchange_rates`, `seasons` (branch_id), `margin_rules` (branch_id), `payment_channel_costs`. **Belum ada satu pun**, DB belum berubah.
- **Enum**: `App\Enums\SeasonType` (peak/high/low), `App\Enums\PaymentChannel` (bank_transfer/international_card).
- **Model Eloquent**: `Currency`, `ExchangeRate`, `Season`, `MarginRule`, `PaymentChannelCost` — semua pakai `LogsActivity`.
- **`App\Casts\MoneyCast`** — cast parameterized buat kolom model yang mau langsung expose `Money`.
- **`RuleResolver`** — cari `Season` yang cocok tanggal keberangkatan → `season_type` → `MarginRule` yang cocok (branch+product_type+season_type), fallback ke rule `season_type=null`, lempar `NoApplicableMarginRuleException` kalau gak ada yang cocok sama sekali.
- **`Converter`** — konversi pakai `bcmath` (`bcmul`/`bcdiv` string decimal), bulat cuma sekali di akhir. Terima locked `ExchangeRate` eksplisit atau resolve yang terbaru sendiri.
- **`PricingEngine::calculate()`** — rumus penuh PRD (cost_total, margin, channel_cost, sell_idr_minor, display_price), kembalikan `PricingBreakdown` rinci, dukung `Override`.
- **`PricingRequest`, `PricingBreakdown`, `Override`** value object — belum ditulis.
- **`SeasonDoesNotOverlap`** validation rule.
- **Policy**: `SeasonPolicy`, `MarginRulePolicy` (pola 403 lintas-cabang kayak M3).
- **6 test file PRD lainnya**: `RuleResolverTest`, `ConverterTest`, `ChannelCostTest`, `PricingEngineTest`, `RateManagementTest`, `SeasonOverlapTest` — **belum ditulis satu pun**. Cakupan 90% PricingEngine yang diminta PRD jelas belum tercapai karena PricingEngine-nya sendiri belum ada.
- **UI**: seluruh Livewire CRUD (Currency/ExchangeRate/Season/MarginRule/PaymentChannelCost List+Form) + halaman **Pricing Simulator** (ini juga satu-satunya tempat buat tes override+reason+activity-log secara manual, karena belum ada quotation yang makan `PricingEngine`).
- **Routes**, **sidebar link baru**, **`lang/{en,id,ar}/pricing.php`**, **`PricingSeeder`** — belum ada.

## Cara lanjut

1. Baca bagian "Keputusan arsitektur" di atas — itu udah final, jangan tanya ulang ke owner kecuali ada alasan kuat berubah.
2. Lanjut dari daftar "Belum dikerjakan" urut dari atas: migrasi+enum dulu, baru model, baru `RuleResolver`/`Converter`/`PricingEngine` (tulis test-nya dulu per masing-masing sebelum nyambung ke UI — persis instruksi PRD langkah 12).
3. Baru sesudah domain core teruji, bangun UI CRUD + Simulator.
4. Jangan lupa jalanin `php artisan test --coverage --min=90 --filter=Pricing` di akhir buat cek DoD cakupan — kalau driver coverage gak keinstall, bilang eksplisit ke owner, jangan diklaim lolos.

## Hasil pengujian sejauh ini

- **74 test PASS** (64 lama + 10 `MoneyTest` baru), 0 regresi.
- Pint bersih.
- Belum ada browser test (belum ada UI buat ditest).
