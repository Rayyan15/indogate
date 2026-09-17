# Rule Project — Indogate Travel Platform

Dokumen ini pegangan bareng (aku & Claude) biar konsisten antar sesi kerja. Ditulis dari kesepakatan langsung di chat, bukan tebakan.

---

## 0. Perintah Owner

**Perintah owner (langsung dari chat) adalah mutlak — melebihi seluruh rule di bawah ini.** Kalau owner minta sesuatu yang bertabrakan dengan rule manapun di file ini, PRD, atau konvensi kode, ikuti perintah owner. Rule di bawah ini default behavior saat tidak ada instruksi eksplisit yang bertentangan.

---

## 1. Sumber kebenaran

- **PRD**: `docs/PRD_Indogate_v3.1.md` — spek fitur, matriks izin, struktur data, DoD tiap modul. Kalau kode dan PRD beda, PRD menang kecuali owner bilang lain.
- **Progress log**: `docs/progress/<kode-modul>-<nama>.md` — satu file per modul selesai. Wajib dibuat/diupdate **setiap kali modul selesai**, isi: ringkasan, yang dibangun, hasil test, keputusan desain yang perlu diingat modul lain, apa yang sengaja belum masuk lingkup.

## 2. Wajib test sebelum lapor selesai

- **Modul dianggap belum selesai sampai ada test yang jalan dan PASS.** Tidak ada pengecualian "nanti aja ditest" — kalau nulis logic baru, nulis test-nya di turn yang sama.
- Urutan yang dipakai: unit/feature test dulu untuk logic inti → build sampai lulus → jalankan `php artisan test` (full suite, bukan cuma filter modul baru) buat cek regresi → `vendor/bin/pint --test` bersih.
- Kalau nemu test lama yang gagal dan **bukan** akibat perubahan sesi ini, buktikan dulu (`git stash` lalu jalanin test itu) sebelum bilang "pre-existing, di luar scope" — jangan asumsi.
- Laporan ke owner setelah modul selesai wajib nyebut: berapa test jalan, berapa PASS, status Pint.
- **Kalau modul nyentuh UI (halaman baru, komponen Livewire, ubah layout/nav), wajib dites di browser sungguhan** (`php artisan serve` + Claude in Chrome / manual) sebelum lapor selesai — test otomatis (`php artisan test`) cuma buktiin logic/HTTP status benar, bukan buktiin halamannya beneran render, komponen Livewire jalan dua arah, modal buka-tutup, dsb. Golden path + minimal satu edge case (contoh: buka sebagai role tanpa izin → cek 403 tampil, bukan cuma asumsi dari test).
- Kalau tidak ada akses browser di sesi itu, bilang eksplisit ke owner "belum dites di browser, cuma test otomatis" — jangan diklaim selesai penuh.

## 3. Arsitektur

- **Selalu pakai komponen** — jangan nulis Blade/logic asal tempel. Livewire 4 dipecah per tanggung jawab (contoh: `UserList` beda file dari `UserForm`, bukan digabung satu component raksasa). Reuse Blade component yang udah ada (`x-modal`, `x-primary-button`, dll) sebelum bikin baru.
- **Policy, bukan cek role manual.** Dilarang `->hasRole('X')` inline di controller/Livewire buat otorisasi aksi. Pakai `$this->authorize()` / `$user->can('domain.aksi')` + Policy class. Middleware `role:`/`permission:` di route level oke buat gerbang area, bukan buat logic per-aksi.
- **Permission pakai penamaan `domain.aksi`** (contoh: `catalog.manage`, `booking.manage`, `payment.verify`) sesuai matriks PRD M1. Jangan bikin permission baru di luar pola ini tanpa update PRD/rule.
- **`branch_id` wajib di setiap tabel transaksional sejak migrasi pertama.** Model baru pakai trait `App\Support\Branch\BelongsToBranch`. Jangan nambah `branch_id` belakangan — PRD eksplisit bilang ini migrasi data besar kalau telat.
- **Direct-access route (show/edit/dll) buat model ber-`branch_id` harus 403 lintas-cabang, bukan 404.** Pola: `Route::bind()` di `AppServiceProvider` yang bypass `BranchScope`, lalu Policy yang eksplisit `abort(403)` kalau `branch_id` beda. Jangan andalkan global scope doang buat kasus ini.
- **`CurrentBranch::id()`** (`App\Support\Branch\CurrentBranch`) satu-satunya sumber "cabang aktif sekarang". Jangan re-resolve manual dari session/user di tempat lain.
- **Uang**: `BIGINT` satuan terkecil + kolom currency terpisah. **Dilarang float** di jalur perhitungan uang mana pun (berlaku mulai M4 Pricing Engine).
- **Status booking** cuma boleh berubah lewat `BookingStateMachine` (mulai M7) — dilarang update kolom status langsung.
- **Segregasi tugas**: satu akun non-Super-Admin dilarang punya `booking.manage` dan `payment.verify` sekaligus. Ditegakkan di kode (`App\Rules\NoConflictingRolePermissions`), bukan cuma didokumentasikan.
- **Teks UI**: lewat file lang, bukan hardcode di Blade (berlaku penuh mulai M2 i18n).
- **Logical properties Tailwind** (`ms`, `me`, `ps`, `pe`) — dilarang `ml`, `mr`, `pl`, `pr` (berlaku penuh mulai M2 RTL).

## 4. Struktur domain

- `app/Domain/*` (Catalog, Pricing, Packaging, dll — per PRD §2.1) **ditunda dibangun sampai modulnya beneran digarap** (M3+). Jangan bikin folder domain kosong duluan.
- `app/Support/*` untuk lintas-modul (Branch, nanti Audit/Localization) — sudah ada `app/Support/Branch/`.

## 5. CI/CD

- `.github/workflows/ci.yml` jalan tiap push/PR ke `main`: composer install → npm build → `pint --test` → `php artisan test`.
- Jangan skip hook/CI check kecuali owner minta eksplisit.

## 6. Alur kerja per modul

1. Baca bagian modul di PRD, pahami Ruang Lingkup + Langkah + DoD.
2. Kalau scope nyentuh arsitektur besar (ubah stack, pola baru) → plan mode + konfirmasi ke owner dulu.
3. Build sesuai pola di atas.
4. Test (unit → feature → full suite → pint).
5. Tulis `docs/progress/<kode>-<nama-modul>.md`.
6. Lapor ke owner: ringkas apa yang jadi, hasil test, apa yang sengaja di luar scope.
