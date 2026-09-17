# M2 — i18n, RTL & Design Token

**Status:** Selesai (scope inti) — lihat "Belum masuk lingkup" · **Tanggal:** 2026-09-17 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M2, §4

## Ringkasan

Sebelum modul ini: `mcamara/laravel-localization` cuma dependency composer, gak pernah dikonfigurasi (no config, no middleware, no prefix). Gak ada `lang/` dir sama sekali. `spatie/laravel-translatable` gak dipakai. 64 class RTL fisik (`ml-`/`mr-`/`pl-`/`pr-`/`text-left`/`text-right`) tersebar di 11 file. Admin panel pakai tema gold/dark custom lewat inline `<style>`, bukan token PRD §4.

## Yang dibangun

**Routing locale — dan kenapa bukan pola resmi package-nya**
- Pola resmi `mcamara/laravel-localization` (`Route::group(['prefix' => LaravelLocalization::setLocale()])`) **terbukti gak jalan** di Laravel 12: `withRouting()` daftar route SEBELUM request per-request kebentuk, jadi `setLocale()` selalu resolve `null`. Dibuktikan lewat log debug langsung, bukan asumsi.
- Ganti pakai `Route::prefix('{locale}')->whereIn('locale', [...])->middleware('setLocale')` — parameter route asli, di-resolve router per-request, robust dan testable.
- **Bug turunan #1 (ditemukan & di-fix)**: dengan `{locale}` jadi route parameter asli, `Illuminate\Routing\ResolvesRouteDependencies` mencocokkan parameter route ke argumen controller method **secara POSISIONAL**, bukan by-name. Controller `show(Booking $booking)` nerima 2 argumen positional (`'id'`, `Booking`) padahal cuma declare 1 param → TypeError. Fix: `App\Http\Middleware\SetLocale` manggil `$request->route()->forgetParameter('locale')` setelah locale dipakai, sebelum request nyampe controller.
- `App\Http\Middleware\SetLocale`: set `App::setLocale()`, simpen ke session, `URL::defaults(['locale'=>...])` biar `route()`/`route('x', $model)` di seluruh app (termasuk yang lama, gak diubah) tetep jalan tanpa perlu sebut locale eksplisit.
- `Route::fallback()` nangkep URL tanpa prefix ATAU prefix gak dikenal (`/login`, `/fr/login`), redirect ke locale sesi/default sambil preserve sisa path — sesuai PRD ("locale tak dikenal dialihkan").
- **Bug pre-existing ditemukan (di luar M2, langsung di-fix)**: `App\Providers\FortifyServiceProvider` **gak pernah terdaftar** di `bootstrap/providers.php` — cuma `AppServiceProvider` yang ada. Artinya seluruh kerja M1 di provider itu (reject login user nonaktif, rate limiter custom) **gak pernah nyala**. Ketauan pas custom `authenticateUsing` gak keliatan efeknya dan Fortify's own route jalan duluan (bentrok `/login`). Fixed: daftarkan provider-nya.
- **Bug pre-existing lain ditemukan & di-fix**: `RegistrationTest` gak pernah `$this->seed()`, padahal `CreateNewUser` assign role `Customer` yang cuma ada kalau di-seed → test itu gagal dari awal (dikonfirmasi lewat `git stash` sebelum M1 disentuh). Sekarang seed di `setUp()`.

**RTL**
- `App\Support\Localization\Direction::current()` — `ar` → `rtl`, selainnya `ltr`. Dipasang di `dir="..."` seluruh layout (`admin`, `guest`, `app`, `customer`).
- 64 class fisik di 11 file diganti ke logical property (`ms-`/`me-`/`ps-`/`pe-`/`text-start`/`text-end`) — batch sed, diverifikasi lewat diff manual + `grep` nol hasil.
- Dites langsung di browser mode Arab: sidebar admin kebalik ke kanan, ikon & label align bener, gak ada elemen numpuk.

**Design token & reskin**
- `tailwind.config.js` diganti total ke token PRD §4.4 (red/blue/neutral/semantic scale, radius kecil, shadow halus).
- Tema gold/dark inline `<style>` di `layouts/admin.blade.php` **dihapus**, dipindah jadi `@layer components` di `resources/css/app.css` pakai token baru (`bg-red-600`, `bg-neutral-900` utk header tabel, dst — persis mapping PRD §4.5). Nama class (`admin-input`, `btn-primary`, `admin-table`, dst) **gak diubah**, jadi 20+ view CRUD admin (hotels/flights/drivers/pricing/bookings/payments) otomatis ke-reskin tanpa disentuh satu-satu. Dicek langsung di browser (halaman Hotels) — tabel header gelap, tombol merah, sesuai PRD.

**i18n**
- `lang/{id,en,ar}/{nav,admin}.php` — chrome admin (sidebar, top bar) full ter-translate, dicoba di browser: ganti locale Indonesia→Arab, semua label sidebar berubah bahasa + arah.
- `App\Livewire\LocaleSwitcher` — dropdown ganti bahasa.
  - **Bug ditemukan & di-fix di browser**: `switchTo()` awalnya baca `request()->path()` di dalam action Livewire — itu path endpoint AJAX (`livewire-hash/update`), BUKAN path halaman! Akibatnya redirect numpuk jadi `/id/id/id/.../id/ar/...` (infinite growing URL). Fix: tangkep path asli di `mount()` (render awal halaman), simpen di property, dipakai di `switchTo()`. Test regresi: `LocaleSwitcherTest`.
- `App\Models\Hotel` — `HasTranslations` (spatie) gantiin manual array cast, `$translatable = ['name','description']`.
- `App\Console\Commands\LangMissing` (`php artisan lang:missing`) — bandingin key antar `lang/id`, `lang/en`, `lang/ar`, laporin yang bolong.

**Format uang & tanggal**
- `App\Support\Localization\Money::format()` — wrap `NumberFormatter`, per currency+locale (IDR pakai "Rp", SAR pakai digit Arab ٠-٩ sesuai locale `ar_SA` — bukan bug, itu perilaku ICU yang benar).
- `App\Support\Localization\DateFormatter::format()` — wrap Carbon `translatedFormat`.

## Tests (`tests/Feature/`, semua PASS — 50 total di suite penuh)

| File | Isi |
|---|---|
| `LocalizationTest.php` | Prefix `/id`,`/en`,`/ar` set locale; tanpa prefix redirect; locale gak dikenal redirect |
| `RtlDirectionTest.php` | `ar` → `dir="rtl"`; lainnya → `dir="ltr"` |
| `MoneyFormatTest.php` | Format IDR/USD/SAR benar per locale |
| `TranslationCompletenessTest.php` | `lang:missing` exit 0 |
| `LocaleSwitcherTest.php` | Regresi bug path-stacking; locale gak didukung diabaikan |

## Browser test (wajib, modul ini UI-heavy)

Login sebagai Super Admin, browser sungguhan (`php artisan serve` + Chrome):
1. Dashboard render tema baru (bukan gold) — **PASS**.
2. Ganti locale ke Arab lewat dropdown — sidebar & konten flip RTL, label ter-translate — **PASS** (setelah fix bug path-stacking).
3. Branch switcher Bali↔Jakarta masih jalan bareng locale switcher — **PASS**.
4. Halaman Hotels (CRUD lama, gak disentuh) — tabel & tombol otomatis kepake token baru — **PASS**.

## Keputusan desain yang perlu diingat modul berikutnya

- **Jangan pakai pola `Route::group(['prefix' => LaravelLocalization::setLocale()])`** — gak jalan di Laravel 12 app ini. Locale routing lewat `{locale}` route parameter + `SetLocale` middleware.
- **Route yang punya route-model-binding wajib lewat middleware yang manggil `forgetParameter('locale')`** sebelum dispatch — kalau nambah middleware/route baru di luar group yang udah ada, pastikan masih lewat `SetLocale`.
- **Jangan baca `request()->path()` di dalam method action Livewire** — itu path endpoint AJAX, bukan path halaman. Tangkep di `mount()` kalau perlu tau URL halaman.
- Class CSS admin (`admin-input`, `btn-primary`, dst) sekarang didefinisikan di `resources/css/app.css` via `@layer components`, bukan inline `<style>` per halaman — kalau nambah komponen UI admin baru, reuse class yang ada dulu sebelum bikin baru.
- `App\Support\Localization\Money`/`DateFormatter` dipakai buat format tampilan sekarang; **bukan** pengganti `Money` value object M4 (BIGINT minor unit) — jangan dicampur pas M4 dibangun.

## Belum masuk lingkup M2 (sengaja, dicatat biar gak ke-klaim selesai)

- **Ekstraksi teks UI ke lang file baru mencakup chrome admin (nav + judul panel).** Isi tiap halaman CRUD (hotels/flights/drivers/bookings/payments/pricing, index+create+edit) dan seluruh storefront customer (cart/checkout/dashboard/search) **masih hardcode teks Inggris/Indonesia campur** — belum di-`__()`-kan. Ini scope besar (58 file), sengaja gak dipaksain sekali jalan biar tiap ekstraksi bisa direview, bukan asal-asalan generate key.
- `lang/{locale}/validation.php` belum dibuat — Laravel 12 skeleton emang gak nyertain default, jadi pesan validasi form saat ini fallback ke bawaan framework (Inggris). Perlu dibuat + terjemahan 3 bahasa sebelum DoD PRD "tidak ada teks antarmuka di luar file lang" bener-bener 100%.
- Terjemahan Arab (`lang/ar/*`) kualitas mesin, ditandai jelas di komentar file — PRD §6 V11 (penutur asli Arab) masih `[ISI]`, jadi ini belum final buat go-live.
- Reskin visual baru nyentuh `layouts/admin.blade.php` + shared CSS classes. Halaman storefront/`layouts/customer.blade.php` masih pakai skema warna lama sendiri (belum ditinjau ulang ke token PRD §4) — di luar scope karena PRD M10 (Storefront Publik) belum jalan.

## Cara verifikasi ulang

```bash
php artisan migrate:fresh --seed
php artisan test
php artisan lang:missing
vendor/bin/pint --test
npm run build
```

Manual: `php artisan serve`, login `admin@indogate.com` / `password`, buka `/id/admin/dashboard`, ganti locale ke Arab lewat dropdown pojok kanan atas, cek RTL + terjemahan sidebar.
