# M5 — Package Builder

**Status:** SELESAI · **Tanggal:** 2026-09-18 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M5

## Keputusan arsitektur (final, dikonfirmasi owner)

1. **Pax scaling**: qty kamar (ROOM) TIDAK ikut pax — konfigurasi eksplisit builder (occupancy adalah keputusan manusia, bukan derivasi otomatis). Semua tipe produk lain (VEHICLE/TICKET/ACTIVITY) qty dikali `ceil(pax/base_pax)`.
2. **Anchor tanggal**: `packages` sengaja **tidak** punya kolom tanggal fix. Builder pakai "tanggal preview" transient (input UI doang, gak disimpan) buat resolve season/rate saat kalkulasi. Tanggal beneran baru dikunci di M6 (Quotation) pas paket dipasang ke booking nyata.
3. **PDF**: `barryvdh/laravel-dompdf` (pure PHP, gak nambah dependency Node/Chrome).
4. **Rate/margin rule hilang per baris**: soft-fail — baris ditandai `rate_missing`, kalkulasi tetap jalan buat baris lain yang valid, gak nge-block seluruh paket.
5. **`packages.duration_days`** dan **`package_items.sort_order`**: dua kolom tambahan di luar daftar literal PRD, wajib buat validasi "total malam konsisten" (langkah 7 PRD) dan persist urutan hasil reorder Alpine.
6. **Domain namespace**: `App\Domain\Packaging\*` (`Package`, `PackageItem`, `PackageDay`, `PackageCalculator`, `PackageCalculationResult`, `PackageItemResult`) — persis nama yang udah dipetakan PRD §2.1.
7. **Permission**: `catalog.manage` buat CRUD paket (CS Admin bisa nyusun), **`pricing.manage`** khusus gate visibility margin/modal (Super Admin doang) — reuse permission existing, gak bikin baru, konsisten pola M4.
8. **Satu Livewire component builder** (`PackageBuilder`), bukan nested sub-component picker — item search dilakukan lewat computed property yang dipanggil dari Alpine `$wire.call()`, biar gak nambah biaya render per-komponen (sesuai warning PRD soal builder harus responsif).
9. **PDF readiness**: polling `wire:poll.3s` (aktif cuma pas `exportPending=true`), BUKAN broadcast/websocket — project ini gak punya infra Reverb/Pusher (`BROADCAST_CONNECTION=log`).

## Pola yang direuse

- **Payload security** (kritis — inti alasan PRD kasih warning eksplisit soal Livewire): pola persis `PricingSimulator` M4 — `PricingBreakdown` (value object domain) TIDAK PERNAH jadi public property. `buildSummaryArray()` di `PackageBuilder` **meng-omit sepenuhnya** (bukan null-kan) key `cost_total`/`margin_percent`/`margin_minor`/`channel_cost` dari array kalau `!$user->can('pricing.manage')`, sebelum di-assign ke `$this->summary` — satu-satunya yang beneran dikirim Livewire ke browser.
- **Translatable field**: pola persis `Partner`/`InventoryItem` (`HasTranslations`) + shape Livewire `PartnerForm` (`public array $name = ['en'=>'','id'=>'','ar'=>'']`).
- **Branch scoping**: `BelongsToBranch` di `Package` doang (bukan di `PackageItem`/`PackageDay` — scope transitif lewat `package_id`, hindari double-scope).
- **Policy**: pola `InventoryItemPolicy` buat `PackagePolicy`.
- **PricingEngine M4 dipakai murni** lewat `PackageCalculator` — gak ada modifikasi ke `app/Domain/Pricing/*` sama sekali.

## Yang sudah jadi (teruji, jalan di browser)

- **3 migrasi** (`packages`, `package_items`, `package_days`) + 3 model.
- **`PackageCalculator`** — resolve `Rate` per baris berdasar anchor tanggal (preview date + day_from offset), soft-fail per baris, agregasi total.
- **Alpine client-side builder** — tambah/hapus/edit baris komponen TANPA request server sama sekali (baru pertama kali dipakai di codebase ini, gak ada pola existing yang bisa direuse, didesain dari nol sesuai instruksi PRD). Server cuma dipanggil pas tombol "Hitung Ulang" ditekan (`$wire.call('recalculate', items, pax)`).
- **`PackageBuilder`** Livewire component (meta paket, item picker via computed property, ringkasan harga margin-filtered) + **`PackageList`** (index + duplikasi).
- **`Package::duplicate()`** — copy independen (id baru semua level, translations di-copy, `is_template` di-reset `false`).
- **Export PDF via queue** (`GeneratePackageItineraryPdf` job, `barryvdh/laravel-dompdf`) + signed download route (`admin.packages.export-download`) + polling readiness.
- Routes `admin.packages.*`, sidebar link "Paket", `lang/{en,id,ar}/packaging.php`.

## Hasil pengujian

- **110 test PASS** (100 lama + 10 baru: `PackageCalculatorTest` x2, `PackagePaxTest` x1, `BuilderTest` x2, `MarginVisibilityTest` x2, `TemplateTest` x1, `ValidationTest` x2), 0 regresi.
- Pint bersih.
- **Dites di browser sungguhan** (login Super Admin + CS Admin terpisah, `php artisan serve`):
  - Susun paket 2 komponen (Suite Taman + Toyota Hiace) lewat Alpine, add/remove client-side tanpa reload — jalan mulus.
  - Hitung Ulang: matematika benar persis (modal 580jt + margin 15% Bali Low season = 87jt, sell 667jt; tambah vehicle qty scale 2x di pax=4 → total 811jt) — dicek manual angka per baris.
  - Ubah pax 2→4: harga kamar TETAP (sesuai keputusan #1), harga kendaraan naik 2x — kebukti kerja di UI beneran, bukan cuma unit test.
  - Simpan → redirect ke halaman edit (fix bug, lihat bawah).
  - **Margin hiding dicek di level payload Livewire langsung** (`Livewire.find(id).get('summary')` dari console browser) sebagai CS Admin: field `cost_total`/`margin_percent`/`margin_minor`/`channel_cost` **betul-betul gak ada** di object manapun — cuma `sell_idr_minor`/`display_price` yang terkirim. Ini pembuktian paling ketat sesuai manual test PRD #6 ("periksa respons jaringan").
  - Export PDF: dispatch job → `php artisan queue:work` proses → polling detect ready → link unduh valid, `fetch()` return `200 application/pdf`.
  - Mode Arab: sidebar kebalik ke kanan, semua label diterjemahkan, layout gak berantakan — tanpa kode RTL bespoke, murni logical Tailwind properties yang udah jadi konvensi project.

## Bug ditemukan & diperbaiki selama sesi ini

1. **DB dev project ternyata SQLite**, bukan MySQL — sesuai permintaan owner, dipindah ke MySQL Laragon (`indogate` database, root/no-password). `.env.testing` tetap sqlite `:memory:` terpisah (gak kesentuh, sesuai rule.md).
2. **Bug lama ke-expose oleh MySQL** (SQLite diam-diam toleransi, MySQL nolak keras): `activity_log.subject_id` kolom `unsignedBigInteger` (default Spatie), tapi `Currency` (M4) pakai primary key string (`code`, misal "KRW") — insert activity log buat Currency APAPUN gagal keras di MySQL. Diperbaiki lewat migrasi baru yang widen kolom jadi `VARCHAR(255)` (no-op di SQLite, cuma jalan kalau driver mysql).
3. **Redirect setelah save() pertama kali gagal 2 lapis**: (a) `route('admin.packages.edit', $package)` salah isi parameter `{locale}` karena app pakai mcamara/laravel-localization dengan `{locale}` prefix — posisi parameter ambigu bikin `$package` object nyasar ngisi slot `{locale}`. (b) Setelah dikasih key eksplisit `['package'=>$package]`, ternyata `{locale}` juga gak ke-fill karena request AJAX Livewire (`/livewire/update`) gak lewat middleware `SetLocale` yang biasanya set `URL::defaults()`. Fix: isi `locale` eksplisit dari `app()->getLocale()`.
4. **PDF download link 404 potensial**: `Storage::disk('local')->url($path)` generate URL `/storage/...` (symlink disk PUBLIC), padahal file PDF disimpan di disk `local` yang di Laravel 12 defaultnya `storage/app/private` — beda lokasi fisik sama sekali dari `/storage` symlink. Diperbaiki pakai signed route (`admin.packages.export-download`, pola sama `PaymentController::downloadProof`) yang stream file langsung dari disk private, bukan URL publik.
5. **UX minor**: setelah `save()` pertama kali di halaman "create", Alpine kehilangan salah satu baris item secara visual (data-nya aman tersimpan di DB, cuma tampilan browser yang glitch). Diperbaiki dengan redirect ke halaman edit setelah create pertama, bukan coba rekonsiliasi state Alpine di tempat.

## Di luar lingkup (sengaja belum digarap)

- Timeline itinerary per hari di UI builder (model `PackageDay` sudah ada dan dipakai PDF export, tapi belum ada layar CRUD-nya di builder — prioritas PRD "Sebaiknya", bukan "Wajib").
- Integrasi nyata ke Quotation (M6 belum digarap) — preview date builder murni transient, belum ada alur "paket → booking".
- Broadcast realtime buat notifikasi PDF siap — sengaja pakai polling karena app ini emang belum punya infra Reverb/Pusher; kalau nanti infra itu ada, bisa upgrade tanpa ubah kontrak `GeneratePackageItineraryPdf`.
