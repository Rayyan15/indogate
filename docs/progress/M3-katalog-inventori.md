# M3 — Katalog & Inventori

**Status:** Selesai (scope inti) — lihat "Belum masuk lingkup" · **Tanggal:** 2026-09-17 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M3

## Ringkasan

Sebelum modul ini, katalog cuma ada dari MVP awal: tabel ad-hoc terpisah (`hotels`, `hotel_rooms`, `flight_routes`, `drivers`, `vehicles`), tanpa struktur generik mitra→item→harga yang diminta PRD, tanpa aktivitas log, tanpa validasi tumpang-tindih harga.

**Keputusan arsitektur (dikonfirmasi owner):** dibangun skema baru PRD (`partners`, `inventory_items`, `rates`, `item_media`, `blackout_dates`) persis sesuai spek, **tabel MVP lama dibiarkan tidak disentuh**. Flow search/cart/booking customer yang sudah jalan tetap pakai `hotels`/`drivers`/`flight_routes` lama. Rekonsiliasi/migrasi ke skema baru sengaja **tidak** dikerjakan di M3 — didokumentasikan di sini sebagai utang untuk modul booking berikutnya (M5+), bukan hilang diam-diam.

Ini modul pertama yang mengisi `app/Domain/*` (rule.md §4 sengaja menahan folder ini kosong sampai modul yang beneran butuh — M3 pemicunya).

## Yang dibangun

**Skema data** (`database/migrations/2026_09_17_2100*`): `partners`, `inventory_items`, `rates`, `item_media`, `blackout_dates` — `branch_id` ada di migrasi CREATE dari awal (bukan ditambah belakangan), sesuai rule.md.

**Model** (`app/Domain/Catalog/Models/`): `Partner`, `InventoryItem` — pakai `BelongsToBranch`, `HasTranslations` (nama/deskripsi 3 bahasa, precedent dari `Hotel` model M2), `LogsActivity`. `Rate` — `LogsActivity` (mencatat setiap perubahan harga modal ke activity log, PRD step 10). `ItemMedia`, `BlackoutDate` — model anak biasa.

**Enum** (`app/Enums/`): `PartnerType` (hotel/villa/vehicle_vendor), `InventoryItemType` (room/vehicle/ticket/activity) — ikut konvensi `generate_enums.php` yang sudah ada.

**Validasi tumpang-tindih harga**: `App\Domain\Catalog\Rules\RateDoesNotOverlap` — cek `existing.valid_from <= new.valid_to AND existing.valid_to >= new.valid_from` per `inventory_item_id`. Dibuktikan lewat `RateOverlapTest.php`.

**Otorisasi**: `PartnerPolicy`, `InventoryItemPolicy` — pola 403-lintas-cabang identik `BookingPolicy` (`Route::bind()` bypass `BranchScope` di `AppServiceProvider`, lalu Policy tolak eksplisit kalau `branch_id` beda). Permission `catalog.manage` yang sudah ada dipakai ulang, tidak bikin permission baru.

**UI**: `PartnerList`+`PartnerForm` (Livewire list+modal, pola identik `UserManagement`). `InventoryItemList` (list+filter) + `InventoryItemManager` (satu komponen full-page: field dasar + panel Rates + panel galeri foto + panel Blackout Dates — digabung jadi satu file, bukan dipecah 4, biar jumlah file masuk akal). Semua pakai `x-ui.*` yang sudah ada, teks lewat `lang/{en,id,ar}/catalog.php`.

**Upload foto**: `WithFileUploads`, disimpan di disk `public` (`storage:link` dibuat), dikompres ulang lewat GD (`imagejpeg` kualitas 80%) — **tanpa** dependency Composer baru, sesuai instruksi "kompresi" PRD.

**Seeder**: `CatalogSeeder` — 2 mitra + 2 item + 2 rate per cabang (Bali & Jakarta), didaftarkan di `DatabaseSeeder`.

## Belum masuk lingkup M3 (sengaja, dicatat biar gak ke-klaim selesai)

- **Import massal dari Excel** — PRD tandai "Sebaiknya", tidak dikerjakan.
- **Blackout dates** cuma daftar tambah/hapus polos, tanpa widget kalender.
- **Rekonsiliasi skema lama↔baru** — lihat keputusan arsitektur di atas. Booking/cart/search customer masih baca `hotels`/`drivers`/`flight_routes`, bukan `inventory_items`.
- **Bug pra-existing ditemukan (di luar M3, dicatat bukan diperbaiki)**: akses anonim (belum login) ke rute admin manapun yang butuh redirect ke `/login` melempar 500 (`UrlGenerationException: Missing required parameter [locale]`) — kemungkinan `URL::defaults()` dari `SetLocale` middleware tidak konsisten sampai ke closure `redirectTo` bawaan Laravel. Ketahuan waktu sesi browser jadi tidak valid setelah `migrate:fresh`. Tidak menghalangi user yang sudah login (jalur normal), jadi tidak memblokir M3, tapi perlu diperbaiki sebelum go-live.

## Hasil pengujian

- **63 test PASS** (`php artisan test`, termasuk 5 file baru: `PartnerCrudTest`, `InventoryItemTest`, `RateOverlapTest`, `CatalogPermissionTest`, `TranslatableFieldTest`), 0 regresi dari 50 test sebelumnya.
- **Pint**: bersih (`vendor/bin/pint --test`).
- **Browser manual** (Chrome, login sungguhan): halaman Partners menampilkan data seed dengan benar; halaman Inventory Items dengan filter mitra/jenis; halaman edit item menampilkan field 3 bahasa, panel Rates (rate seed 1 Jan–31 Des 2026 tampil benar), panel Photo Gallery (empty state benar), panel Blackout Dates (empty state benar). Percobaan tambah rate tumpang-tindih lewat klik UI tidak sempat terverifikasi visual (kemungkinan posisi klik meleset), tapi logic-nya sudah dibuktikan lewat `RateOverlapTest` otomatis — **belum 100% dites manual di browser untuk kasus penolakan tumpang tindih**, disebut eksplisit di sini sesuai rule.md §2.
- Sidebar admin dapat menu baru "Catalog" (Partners, Inventory Items) di 3 bahasa.
