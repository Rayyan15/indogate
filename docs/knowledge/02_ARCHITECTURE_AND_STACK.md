# 02. Arsitektur dan Tumpukan Teknologi — Indogate

---

## 1. Tumpukan Teknologi Aktual (Actual Stack)

> [!IMPORTANT]
> Repositori ini **tidak menggunakan Vue, Inertia, maupun Flutter**. 
> Seluruh antarmuka web, portal admin, dan storefront publik dibangun di atas:
> - **Backend Framework:** Laravel 12.0
> - **Komponen Reaktif:** Livewire 4.4 (dengan Alpine.js yang terintegrasi di dalamnya)
> - **Template Engine:** Laravel Blade View Components
> - **Styling:** Tailwind CSS 3 (v3.1.0 via PostCSS)
> - **Autentikasi:** Laravel Fortify 1.39 (mode headless/callback) + Laravel Breeze Blade views
> - **Otorisasi & RBAC:** Spatie Laravel Permission 6.25
> - **Audit Trail:** Spatie Laravel Activitylog 4.12
> - **Multibahasa / i18n:** Spatie Laravel Translatable 6.11 + Mcamara Laravel Localization 2.4
> - **Ekspor Dokumen:** Barryvdh Laravel DomPDF 3.1
> - **Basis Data:** SQLite (lokal in-memory untuk testing) / MySQL (Laragon lokal & produksi)
> - **Asset Bundler:** Vite 7.0 + laravel-vite-plugin 2.0

---

## 2. Struktur Model Dual-Layer (Domain-Driven Evolution)

Arsitektur aplikasi memiliki dua lapisan model yang hidup berdampingan secara harmonis:

1. **Lapisan Domain Enterprise (`app/Domain/`)**:
   Struktur modern Domain-Driven Design (DDD) yang menampung model bisnis inti, value object, dan service:
   - `App\Domain\Booking\Models\*`: `PackageBooking`, `BookingGuest`, `BookingNote`, `BookingStatusHistory`.
   - `App\Domain\Catalog\Models\*`: `Partner`, `InventoryItem`, `Rate`, `ItemMedia`, `BlackoutDate`.
   - `App\Domain\Finance\Models\*`: `PaymentIntent`, `Payment`, `VendorPayment`, `Refund`.
   - `App\Domain\Fleet\Models\*`: `Driver`, `Vehicle`, `DriverAssignment`.
   - `App\Domain\Lead\Models\*`: `Lead`, `LeadActivity`, `Quotation`, `QuotationItem`.
   - `App\Domain\Packaging\Models\*`: `Package`, `PackageItem`, `PackageDay`.
   - `App\Domain\Pricing\Models\*`: `Currency`, `ExchangeRate`, `Season`, `MarginRule`, `PaymentChannelCost`.

2. **Lapisan Kompatibilitas MVP (`app/Models/`)**:
   Menampung model infrastruktur dan wrapper kompatibilitas:
   - `User`, `Branch`: Model sistem inti.
   - `Driver`, `Vehicle`: Langsung mewarisi (`extends`) model domain `App\Domain\Fleet\Models\*` agar controller lawas tetap berfungsi.
   - Model historis MVP awal (`Customer`, `Hotel`, `HotelRoom`, `FlightRoute`, `TourAddon`, `Booking`, `BookingItem`, `PricingRule`): Tetap dipertahankan untuk menjamin backward-compatibility transaksi lama.

---

## 3. Arsitektur Routing dan Middleware Pipeline

### 3.1 Prefix Wajib `{locale}`
Seluruh endpoint aplikasi (Storefront, Autentikasi, Admin, Customer) dibungkus di bawah parameter rute `{locale}`:
```php
Route::prefix('{locale}')
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales')))
    ->middleware('setLocale')
    ->group(...)
```
Bahasa yang didukung:
- `id`: Bahasa Indonesia (default aplikasi).
- `en`: English (internasional).
- `ar`: Arabic (Arab Saudi / kawasan GCC, memicu mode RTL).

### 3.2 Penanganan Fallback dan Livewire AJAX
- **Root Redirect (`/`)**: Mengarahkan otomatis ke `/{locale}` default (`/id`).
- **Rute Fallback**: Menangani URL tanpa segmen bahasa atau kode bahasa tidak sah (misal `/login` atau `/fr/dashboard`) dengan memotong segmen salah dan mengalihkan ke rute yang valid.
- **Middleware `SetLocale`**:
  - Diletakkan di urutan teratas sebelum `AuthenticatesRequests` di `bootstrap/app.php`.
  - Mengisi `URL::defaults(['locale' => $locale])` agar helper `route('login')` tidak gagal saat terjadi redirect otentikasi.
  - Memanggil `$request->route()->forgetParameter('locale')` agar argumen controller dengan Route Model Binding tidak bergeser secara posisional.
- **Middleware `EnsureLocaleUrlDefault`**:
  - Terdaftar di grup `web` untuk menangani request AJAX background Livewire (`/livewire/update`) yang berada di luar rute prefix `{locale}`.
- **Middleware `SetActiveBranch`**:
  - Mengatur branch aktif pengguna dalam session, serta mencabut override session jika pengguna kehilangan izin `branch.switch`.

---

## 4. Autentikasi, Otorisasi, dan Isolasi Cabang

### 4.1 Laravel Fortify Headless Callback
- `Fortify::$registersRoutes = false;` diatur di `FortifyServiceProvider`. Seluruh rute autentikasi dikendalikan oleh Blade views di `routes/auth.php`.
- Callback otentikasi kustom memeriksa status aktif pengguna:
  ```php
  Fortify::authenticateUsing(function ($request) {
      $user = User::where('email', $request->email)->first();
      if ($user && $user->is_active && Hash::check($request->password, $user->password)) {
          return $user;
      }
      return null;
  });
  ```
  Pengguna dengan `is_active = false` otomatis ditolak masuk ke dalam sistem.

### 4.2 Matriks Peran dan Izin (Spatie RBAC)
Format izin mengikuti konvensi `domain.aksi`:
- `catalog.manage`, `pricing.manage`, `currency.manage`, `lead.manage`, `quotation.create`, `booking.manage`, `payment.verify`, `driver.assign`, `report.margin.view`, `user.manage`, `activitylog.view`, `branch.switch`.

Matriks Peran:
- **Super Admin:** Memiliki seluruh 12 izin.
- **CS Admin:** Berfokus pada katalog, lead, penawaran, booking, dan supir (`catalog.manage`, `lead.manage`, `quotation.create`, `booking.manage`, `driver.assign`, `report.margin.view` [parsial]). Dilarang memverifikasi pembayaran.
- **Finance Admin:** Berfokus pada kurs, verifikasi pembayaran, dan margin riil (`currency.manage`, `payment.verify`, `report.margin.view` [penuh]). Hanya memiliki hak baca (*view-only*) pada booking.
- **Customer:** Pengguna publik tanpa hak administratif.

### 4.3 Segregasi Tugas (Segregation of Duties - SoD)
- Aturan validasi `App\Rules\NoConflictingRolePermissions` melarang pemberian kombinasi izin `booking.manage` dan `payment.verify` pada akun non-Super Admin secara bersamaan.
- `PaymentService::verify()` memproteksi *anti-self-approval*: staf yang membuat sebuah booking dilarang memverifikasi pembayarannya sendiri (`SelfApprovalException`).

### 4.4 Isolasi Cabang (Multi-Branch Isolation)
- Sumber kebenaran cabang aktif: `App\Support\Branch\CurrentBranch::id()`.
- Model domain menggunakan trait `BelongsToBranch` yang menerapkan `BranchScope` (menambahkan klausa `WHERE branch_id = CurrentBranch::id()`).
- **Proteksi HTTP 403 Forbidden**: Di `AppServiceProvider::boot()`, 13 entitas rute utama di-bind dengan `withoutGlobalScope(BranchScope::class)`:
  ```php
  Route::bind('packageBooking', fn ($id) => PackageBooking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
  ```
  Hal ini mencegah aplikasi mengembalikan 404 Not Found saat URL cabang lain ditebak. Model tetap dimuat, lalu Policy memeriksa kecocokan `branch_id` dan secara eksplisit melempar respons **HTTP 403 Forbidden**.
