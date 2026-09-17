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

## Bug pra-existing ditemukan & diperbaiki (di luar scope M3, tidak sengaja ditemukan)

Akses anonim (belum login) ke rute admin manapun 500 (`UrlGenerationException: Missing required parameter [locale]`) alih-alih redirect bersih ke `/login`. Ketahuan waktu sesi browser invalid setelah `migrate:fresh`.

**Akar masalah** (dibuktikan lewat `Kernel::handle()` manual di tinker, bukan tebakan): Laravel punya `$middlewarePriority` bawaan yang menaruh `Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests` di posisi tetap. Middleware custom yang **tidak** ada di list itu — termasuk `setLocale` — ikut di-reorder oleh `SortedMiddleware`, dan `auth` selalu dimajukan duluan **apa pun urutan penulisan di `routes/web.php`**. Akibatnya `URL::defaults(['locale' => ...])` (dipanggil `SetLocale` middleware) belum sempat jalan saat middleware `auth` bawaan Laravel mencoba `redirectTo()` → `route('login')` tanpa parameter locale → exception.

**Perbaikan**: `bootstrap/app.php` — `$middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: SetLocale::class)`, memaksa `SetLocale` selalu jalan sebelum `auth` berapa pun urutan di route file. (Sempat salah taruh anchor `Authenticate::class` tanpa `use` import yang benar — resolve jadi string `"Authenticate"` tanpa namespace, gak pernah match, jadi silent-append di akhir list alih-alih ke posisi yang benar. Diperbaiki jadi `AuthenticatesRequests::class` — interface yang benar-benar ada di list bawaan Laravel.)

**Test regresi**: `tests/Feature/LocaleRedirectTest.php` — akses `/en/admin/dashboard` tanpa login harus redirect 302 ke `/en/login`, bukan 500.

**Verifikasi**: `curl` tanpa cookie ke `/en/admin/dashboard` → `302` ke `http://127.0.0.1:8000/en/login` (sebelumnya 500). Log `storage/logs/laravel.log` bersih dari `UrlGenerationException` setelah fix. Full test suite 64/64 pass, Pint bersih.

## Hasil pengujian

- **64 test PASS** (`php artisan test`, termasuk 5 file M3 baru + `LocaleRedirectTest` regresi bug login), 0 regresi.
- **Pint**: bersih (`vendor/bin/pint --test`).
- **Browser manual** (Chrome, login sungguhan): halaman Partners menampilkan data seed dengan benar; halaman Inventory Items dengan filter mitra/jenis; halaman edit item menampilkan field 3 bahasa, panel Rates (rate seed 1 Jan–31 Des 2026 tampil benar), panel Photo Gallery (empty state benar), panel Blackout Dates (empty state benar). Percobaan tambah rate tumpang-tindih lewat klik UI tidak sempat terverifikasi visual (kemungkinan posisi klik meleset), tapi logic-nya sudah dibuktikan lewat `RateOverlapTest` otomatis — **belum 100% dites manual di browser untuk kasus penolakan tumpang tindih**, disebut eksplisit di sini sesuai rule.md §2.
- Sidebar admin dapat menu baru "Catalog" (Partners, Inventory Items) di 3 bahasa.
