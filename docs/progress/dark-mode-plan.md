# Rencana Dark Mode — Panel Admin

Status: **rencana, belum dikerjakan** · Dibuat: 2026-09-23

## 1. Tujuan & cakupan

Staf (CS, Finance, Super Admin) bisa memilih tampilan terang / gelap / ikuti sistem di panel admin. Pilihan tersimpan per perangkat dan tidak ada kedipan putih saat halaman dimuat.

**Termasuk:** semua halaman di `layouts/admin.blade.php` (Meja CS, lead, booking, katalog, keuangan, laporan, pengaturan), komponen `x-ui.*`, modal, pagination admin, halaman login/auth.

**Tidak termasuk:**
- Storefront publik (`layouts/public`, `views/public`, `livewire/public`). Tampilannya sudah gelap dan itu area rekan tim (aturan owner: hanya logic, bukan class/style).
- PDF (voucher, itinerary, duty letter) dan email. Keduanya dicetak atau dikirim, jadi tetap terang.
- Halaman customer (`/dashboard`, profile). Bisa jadi fase lanjutan kalau diminta.

## 2. Kondisi sekarang (hasil audit)

- `tailwind.config.js` sudah `darkMode: 'class'`, tapi belum ada satu pun kelas `dark:`.
- Palet ada di token PRD §4.4: `neutral` 0–900, `red`, `blue`, plus `success/warning/danger/info`.
- Pemakaian di view (±105 file admin): `text-neutral-*` ±790, `border-neutral-*` ±370, `bg-neutral-*` ±290, `bg-white` 90, `text-red-*` ±160, `bg-red-50` 22.
- Warna hex hardcode (`bg-[#030b14]`, `bg-[#051121]`, ...) hanya muncul di storefront, jadi di luar cakupan.

## 3. Pendekatan: token CSS variable, bukan `dark:` per kelas

Menambah `dark:` ke ±1.700 kelas akan memakan waktu dan gampang ada yang terlewat. Alternatifnya:

1. Ubah palet `neutral` (dan tint `red`/`blue` 50–200 serta warna status) di `tailwind.config.js` jadi CSS variable:
   ```js
   neutral: { 0: 'rgb(var(--n-0) / <alpha-value>)', 50: 'rgb(var(--n-50) / <alpha-value>)', /* ... */ 900: 'rgb(var(--n-900) / <alpha-value>)' }
   ```
2. Di `resources/css/app.css`, `:root` berisi nilai terang (sama persis dengan sekarang, jadi mode terang tidak berubah), dan `.dark` berisi skala yang dibalik:
   ```css
   :root { --n-0: 255 255 255; --n-50: 248 248 247; /* ... */ --n-900: 26 26 25; }
   .dark { --n-0: 23 23 22; --n-50: 28 28 27; --n-100: 36 36 34; /* ... */ --n-900: 242 242 240; }
   ```
   Hasilnya `bg-neutral-0` jadi permukaan gelap dan `text-neutral-900` jadi teks terang, otomatis di semua file.
3. Merah brand `#C41230` tetap untuk tombol primer (aturan PRD "satu tombol merah per layar"). Tint `red-50/100` dan `text-red-600/700` diberi nilai gelap yang kontrasnya cukup, target WCAG AA 4.5:1 untuk teks.
4. Sisa yang tidak ikut token dibetulkan manual:
   - `bg-white` → `bg-neutral-0` (±90 tempat di file admin, termasuk `components/modal`, `vendor/pagination`, `vendor/livewire`, lead-list, package-builder).
   - `shadow-*` di config memakai `rgb(26 26 25 / x)`. Di mode gelap diganti border halus atau shadow lebih pekat.
   - `@tailwindcss/forms`: input date/select/checkbox perlu `color-scheme: dark` di `.dark` supaya picker bawaan browser ikut gelap.

Karena variabel cuma aktif kalau `<html>` punya kelas `dark`, dan kelas itu hanya dipasang oleh layout admin, storefront pasti tidak ikut berubah.

## 4. Toggle & persistensi

- Tombol di top bar admin (dekat pemilih bahasa) dengan 3 pilihan: Terang / Gelap / Sistem. Pakai Alpine, tanpa library baru.
- Disimpan di `localStorage('theme')`. Default: **Sistem** (`prefers-color-scheme`).
- Script inline kecil di `<head>` layout admin, sebelum `@vite`, memasang kelas `dark` sebelum halaman dirender. Tujuannya mencegah kedipan putih.
- Livewire `wire:navigate` / morph tidak menyentuh `<html>`, jadi kelas tetap bertahan. Tetap perlu diverifikasi.
- Tidak disimpan di DB, karena per perangkat sudah cukup (YAGNI). Kalau nanti diminta "ikut akun", tambah kolom `users.theme`.

## 5. Tahapan kerja

| # | Pekerjaan | File utama | Estimasi |
|---|-----------|------------|----------|
| 1 | Token CSS variable + nilai terang identik; pastikan tampilan terang tidak berubah sama sekali | `tailwind.config.js`, `resources/css/app.css` | 0,5 hari |
| 2 | Skala gelap `.dark` untuk neutral, tint red/blue, status, shadow, `color-scheme` | `app.css` | 0,5 hari |
| 3 | Toggle + script anti-kedip + string i18n (id/en/ar) | `layouts/admin.blade.php`, `lang/*/nav.php` | 0,5 hari |
| 4 | Sapu `bg-white` dan hardcode lain di file admin | ±10 file | 0,5 hari |
| 5 | QA visual per halaman (lihat §6), perbaiki kontras | — | 1 hari |
| 6 | Auth/login ikut tema | `layouts/guest.blade.php` | 0,25 hari |

Total ± **3 hari kerja**.

## 6. Checklist QA (Chrome, terang dan gelap, LTR dan RTL `ar`)

- Meja CS, Dashboard eksekutif (grafik/angka), Laporan.
- Lead list/form + Buat Penawaran; Booking list/detail (badge status, riwayat, pembayaran).
- Katalog: hotel, item, paket + Package Builder (tabel ringkasan, margin).
- Keuangan: pembayaran, piutang, vendor, margin report.
- Pricing engine (tabel kurs, simulator), Kelola User, Hak Akses Role (checkbox terkunci), Audit log.
- Modal, dropdown, flash sukses/error, pagination, input date/select, state kosong.
- Tidak ada kedipan putih saat refresh; pilihan tetap tersimpan setelah logout/login.
- Storefront, PDF voucher, dan email **tidak berubah** (regresi).
- Kontras: teks utama ≥ 4.5:1, teks sekunder `neutral-500` tetap terbaca.

## 7. Test otomatis

- Feature test: layout admin merender script tema dan toggle; layout publik **tidak** merender keduanya.
- Test lama (303) harus tetap hijau. Dark mode murni CSS/Alpine, jadi logic tidak berubah.

## 8. Risiko

- **Grafik dashboard**: kalau warna dikirim ke library chart sebagai hex, warnanya perlu dibaca dari CSS variable. Cek saat tahap 5.
- **Opacity arbitrer** (`bg-neutral-900/40` untuk backdrop): dengan `<alpha-value>` ini tetap jalan, tapi backdrop mungkin perlu tetap hitam di mode gelap.
- **Keputusan desain**: nuansa gelap hangat (mengikuti neutral yang agak hangat sekarang) atau navy seperti storefront (`#030b14`). Rekomendasi: **gelap hangat** untuk admin, supaya konsisten dengan identitas editorial panel dan tetap terbedakan dari storefront. Perlu konfirmasi owner sebelum tahap 2.

## 9. Keputusan yang dibutuhkan dari owner

1. Nuansa gelap: hangat (rekomendasi) atau navy seperti storefront?
2. Default untuk staf baru: ikuti sistem (rekomendasi) atau selalu terang?
3. Halaman customer (`/dashboard`) ikut di fase ini atau nanti?
