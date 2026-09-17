# M1 — Auth, RBAC & Multi-cabang

**Status:** Selesai · **Tanggal:** 2026-09-17 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M1

## Ringkasan

Repo sebelumnya pakai Controller+Blade klasik, tanpa `branch_id`, tanpa Policy, RBAC hanya lewat middleware route dengan permission longgar (`manage hotels`, dst). Modul ini membangun pondasi multi-cabang + RBAC granular sesuai PRD, plus sepotong "M0" yang belum ada (tabel `branches`, `branch_id` di seluruh tabel transaksional) — karena M1 tidak bisa benar tanpa itu.

## Yang dibangun

**Multi-cabang**
- Tabel `branches` (Bali, Jakarta) + `branch_id` di `users` dan 8 tabel transaksional (`hotels`, `hotel_rooms`, `flight_routes`, `vehicles`, `drivers`, `bookings`, `payments`, `pricing_rules`), dibackfill ke Bali saat migrasi.
- `App\Support\Branch\CurrentBranch` — resolver tunggal "cabang aktif saat ini" (session override untuk Super Admin, fallback ke `user->branch_id`).
- `App\Support\Branch\BelongsToBranch` (trait) + `BranchScope` (global scope) — query otomatis terfilter cabang aktif; auto-isi `branch_id` saat create.
- **Direct-access 403, bukan 404**: `Route::bind()` untuk `booking`/`payment` di `AppServiceProvider` sengaja bypass `BranchScope`, lalu Policy (`BookingPolicy`, `PaymentPolicy`) yang eksplisit menolak (403) kalau `branch_id` tidak cocok. Kalau cuma andalkan global scope, record lintas-cabang akan 404 diam-diam — PRD melarang ini.
- `App\Livewire\Admin\BranchSwitcher` — dropdown ganti cabang, khusus pemegang permission `branch.switch`.

**RBAC**
- Permission direname ke konvensi `domain.aksi` sesuai matriks PRD: `catalog.manage`, `pricing.manage`, `currency.manage`, `lead.manage`, `quotation.create`, `booking.manage`, `payment.verify`, `driver.assign`, `report.margin.view`, `user.manage`, `activitylog.view`, `branch.switch`.
- 4 role: Super Admin (semua), CS Admin, Finance Admin, Customer (tanpa permission).
- Policy (`BookingPolicy`, `PaymentPolicy`, `UserPolicy`) menggantikan cek role manual di controller — didaftarkan lewat `Gate::policy()` di `AppServiceProvider`.
- **Segregasi tugas**: `App\Rules\NoConflictingRolePermissions` menolak assignment role yang membawa `booking.manage` **dan** `payment.verify` sekaligus (kecuali Super Admin) — dipakai di `UserForm` Livewire.
- User nonaktif (`is_active=false`) ditolak login — `Fortify::authenticateUsing()` custom di `FortifyServiceProvider`.
- `User` model: `LogsActivity` (spatie/laravel-activitylog) mencatat perubahan `branch_id`/`is_active`.

**UI (Livewire 4, komponen dipisah per tanggung jawab)**
- `Admin\UserManagement\UserList` — tabel + pencarian + paginasi.
- `Admin\UserManagement\UserForm` — modal create/edit (reuse `x-modal` yang sudah ada), assign role tunggal + cabang + status aktif.
- `Admin\BranchSwitcher` — dropdown cabang di top bar admin.
- Sidebar admin (`layouts/admin.blade.php`) diupdate: `@can` pakai permission baru, tambah menu "Kelola User".

**CI/CD**
- `.github/workflows/ci.yml` — jalan di push/PR ke `main`: install composer+npm, build asset, `pint --test`, `php artisan test` (sqlite in-memory sesuai `phpunit.xml`).

## Tests (`tests/Feature/`)

| File | Isi |
|---|---|
| `RoleAssignmentTest.php` | Role tersimpan; role dgn permission konflik ditolak |
| `BranchScopeTest.php` | List terfilter cabang; Super Admin switch mengubah data; direct URL lintas-cabang → 403 |
| `PermissionTest.php` | CS/Finance/Customer masing-masing hanya akses rute yang diizinkan |
| `SegregationOfDutiesTest.php` | Role CS & Finance seed tidak pernah tumpang tindih; rule validasi menolak kombinasi terlarang |
| `UserManagementTest.php` | Hanya pemegang `user.manage` bisa buka halaman & mount komponen |

Semua 14 test **PASS** (13 awal + 1 regresi dari temuan browser test, lihat bawah). `vendor/bin/pint --test` **PASS** (juga membersihkan style file lama yang belum pernah dirapikan).

## Browser test (bukan cuma `php artisan test`)

Dites nyata di `php artisan serve` + Chrome, login sebagai tiap role, sampai ketemu **2 bug yang lolos dari test otomatis**:

1. **`resources/js/app.js` start Alpine.js manual** (`Alpine.start()`), padahal Livewire 4 sudah bundling & start Alpine sendiri lewat `@livewireScripts`. Dua instance Alpine bentrok → `wire:submit` di `UserForm` gagal ke-intercept, form modal "Tambah User" jatuh ke native HTML submit (GET kosong ke `/admin/users?`), bukan request Livewire. **Fix:** hapus `Alpine.start()` manual dari `app.js`, biar Livewire yang pegang Alpine sepenuhnya. Ini bug lama (pre-existing), baru ketahuan karena modul ini modul pertama yang benar-benar pakai form Livewire+Alpine bareng.
2. **`UserList` gak refresh setelah `UserForm` sukses simpan.** User baru KE-SIMPAN di DB (dibuktikan lewat tinker), tapi tabel di layar tetap nampilin data lama — karena Livewire gak otomatis re-render sibling component tanpa listener eksplisit. Sempat kepotong pas "beres-beres" awal (dikira no-op). **Fix:** balikin `#[On('user-saved')] public function refresh(): void {}` di `UserList`. Test regresi ditambah: `UserManagementTest::test_user_list_refreshes_after_a_user_is_saved_elsewhere`.
3. Juga ketauan: route `admin/users` awalnya render `UserList::class` langsung sebagai full-page Livewire component, jadi TIDAK kebungkus `<x-admin-layout>` (halaman muncul polos tanpa sidebar/tema). **Fix:** ganti jadi view tipis `resources/views/admin/users/index.blade.php` yang bungkus `@livewire(...)` dalam `<x-admin-layout>`, sama seperti pola halaman admin lain.

**Pelajaran:** ketiga bug ini gak kedeteksi 13 test otomatis awal — test HTTP/Livewire component-level gak nangkep masalah render layout, dan gak nangkep race antar dua Livewire component beneran di browser. Makanya `rule.md` §2 sekarang wajib browser test buat modul yang nyentuh UI.

## Keputusan desain yang perlu diingat modul berikutnya

- **Jangan** cek `->hasRole()` manual di controller/Livewire — selalu lewat Policy atau `$user->can('domain.aksi')`.
- Model transaksional baru **wajib** `use BelongsToBranch;` + kolom `branch_id` di migrasinya sejak awal (lihat pola di `app/Models/Hotel.php` dst).
- Kalau modul baru butuh direct-access route (`show`, `edit`, dll) untuk model ber-`branch_id`, ikuti pola `Route::bind()` bypass-scope + Policy 403 seperti `Booking`/`Payment` — **jangan** andalkan global scope saja untuk itu.
- `CurrentBranch::id()` adalah satu-satunya sumber kebenaran cabang aktif — pakai ini, jangan re-resolve dari session/user manual di tempat lain.
- **Jangan panggil `Alpine.start()` manual di `app.js`.** Livewire sudah bundling Alpine sendiri — nambah instance kedua bikin directive (`wire:submit`, dll) diam-diam gagal tanpa error di console yang jelas.
- Full-page Livewire component untuk area admin **jangan** dirender langsung dari route (`Route::get(..., UserList::class)`). Selalu bungkus lewat view tipis `<x-admin-layout>@livewire(...)</x-admin-layout>` — pola yang dipakai `admin/users/index.blade.php`.
- Livewire component yang harus tampil fresh setelah sibling-nya nulis data **wajib** punya `#[On('nama-event')]` listener, walau isinya kosong — tanpa itu Livewire tidak re-render dia.

## Belum masuk lingkup M1 (sengaja)

- `app/Domain/*` module split (Catalog, Pricing, dst.) — ditunda ke M3+ saat modulnya benar-benar dibangun.
- 2FA admin (PRD: "Sebaiknya", bukan wajib).
- Perbaikan `RegistrationTest` yang gagal — **pre-existing**, tidak terkait perubahan M1 (dikonfirmasi lewat `git stash` sebelum touch apa pun), didaftarkan sebagai utang terpisah, bukan bagian M1.

## Cara verifikasi ulang

```bash
php artisan migrate:fresh --seed
php artisan test --filter="RoleAssignment|BranchScope|Permission|SegregationOfDuties|UserManagement"
vendor/bin/pint --test
```

Login manual: `admin@indogate.com` / `cs.bali@indogate.com` / `finance.bali@indogate.com` / `customer@indogate.com`, password `password` — semua di cabang Bali.
