# UI/UX Specification — Indogate Editorial System

Stack: Laravel 12 · Livewire 4 · Alpine · Tailwind 3. Token warna = `tailwind.config.js` (PRD §4).
Komponen hidup = `resources/views/components/ui/*` (`<x-ui.*>`). Contoh semua komponen dalam satu layar: `/admin/styleguide` (env local).

Dokumen ini adalah **aturan operasional**, bukan mood board. Kalau ragu, buka styleguide, tiru.

---

## 0. Audit tampilan sekarang (kenapa terasa "AI slop")

Ditemukan di 28 file view (September 2026):

| Gejala | Contoh file | Kenapa murah |
|---|---|---|
| `text-white`, `text-slate-*` di atas kartu putih | `admin/dashboard`, `admin/bookings/show` | sisa tema gelap lama → teks nyaris tak terbaca |
| Emas `#D4AF37`, `btn-gold` gradien, `card-premium` glass-blur | `layouts/customer`, `customer/search` | "luxury" versi template Dribbble 2021, di luar token PRD |
| `rounded-full` pill status, `rounded-2xl` kartu, glow blur dekoratif | hampir semua | ciri paling khas template generik (PRD §4.4) |
| Grid 4 kartu KPI identik + grid 3 kartu identik + grid 2 kartu identik | `admin/dashboard` | tidak ada hierarki, mata tidak tahu mulai dari mana |
| Warna inline `style="color:#34d399"` | dashboard, bookings | keluar dari token, mode gelap nanti mustahil |
| Heading `text-lg font-semibold` di bar 64px | `layouts/admin` | halaman tanpa "judul", semua terasa sama |

Semua itu diganti oleh aturan di bawah. Jangan tambal satu-satu; refactor per layar pakai komponen.

---

## 1. Tiga sumber "mewah" (dan bukan warna)

1. **Kontras tipografi ekstrem.** Judul serif tipis dan besar (`font-display font-light text-4xl`) di samping label 10px kapital renggang (`x-ui.eyebrow`) dan angka mono tabular. Jarak ukuran minimal 3 langkah skala. Kalau semua teks `text-sm font-semibold`, layar mati.
2. **Hairline, bukan bayangan.** Struktur dibentuk garis 1px `neutral-200` di kanvas `neutral-50`. `shadow-sm` hanya untuk elemen yang benar-benar melayang (folio sticky, dropdown). `shadow-md/lg` dilarang di permukaan statis.
3. **Asimetri yang disengaja.** Layar kerja = 8/4 (`lg:grid-cols-12` → `col-span-8` + `col-span-4`). Bukan 6/6, bukan 3 kolom kartu sama lebar. Kolom sempit selalu berisi ringkasan yang *menempel* (`sticky top-24`), sehingga total/status tidak pernah hilang saat scroll.

---

## 2. Token yang wajib dipakai

| Peran | Kelas | Catatan |
|---|---|---|
| Kanvas halaman | `bg-neutral-50` | tidak pernah `bg-white` polos satu halaman penuh |
| Permukaan | `bg-neutral-0 border border-neutral-200 rounded` | `x-ui.panel` |
| Judul halaman | `font-display font-light tracking-tight text-3xl/4xl text-neutral-900` | Fraunces |
| Judul panel | `font-display text-lg` | |
| Eyebrow / label meta | `text-[10px] font-bold uppercase tracking-[0.18em] text-neutral-400` | `x-ui.eyebrow` |
| Angka, kode, tanggal | `font-mono tabular-nums` | JetBrains Mono. Selalu `text-end` di tabel |
| Teks isi | `text-sm text-neutral-700` | sekunder `text-xs text-neutral-500` |
| Tombol utama | `x-ui.button variant="primary"` | **satu per layar** |
| Tombol lain | `secondary` (bingkai), `ghost` (teks garis bawah), `danger` (teks abu → merah saat hover) | |
| Tautan | `text-blue-600 underline-offset-2 hover:text-blue-700` | biru tidak pernah jadi tombol |
| Radius | `rounded-sm` 2px, `rounded` 4px, `rounded-md` 6px | `rounded-lg/xl/2xl/full` dilarang kecuali dot 6px dan avatar |
| Spasi horizontal | `ps- pe- ms- me- text-start text-end border-s` | RTL Arab wajib jalan tanpa ubah view |

Warna di luar token = bug. Kalau butuh, tambah ke `tailwind.config.js` dulu.

---

## 3. Aturan komposisi layar (yang bikin tidak monoton)

### 3.1 Setiap layar punya empat lapisan, urut dari atas
1. **`x-ui.page-header`** — eyebrow konteks (cabang · modul · status dot), judul serif, `lede` satu kalimat, aksi di kanan. Ini "sampul" halaman. Tanpa ini halaman terlihat seperti tab admin generik.
2. **Strip angka** (opsional) — `x-ui.stat` berjajar, dipisah hairline kiri, **bukan kotak**. Maksimal 4. Angka besar mono, label eyebrow, catatan 11px.
3. **Badan 8/4** — kiri = daftar/segmen/tabel; kanan = `x-ui.folio` (uang) atau panel ringkasan sticky (status, tamu, dokumen).
4. **Satu tombol merah** di folio kanan. Aksi lain turun jadi `secondary`/`ghost`.

### 3.2 Ritme, bukan pengulangan
- Dilarang dua grid berturut-turut dengan jumlah kolom sama. Setelah grid 4 stat, lanjut 8/4; setelah tabel, lanjut 2 kolom berbeda isi (form + empty state, bukan form + form).
- Daftar item dipakai **`x-ui.segment`** bernomor `01 02 03` dalam wadah `divide-y`, bukan kartu-kartu terpisah bertumpuk dengan gap. Nomor mono memberi mata jangkar.
- Panel dengan header (`eyebrow` + judul + aksi) dan panel tanpa header diselang-seling. Jangan semua panel ber-header.
- Padding vertikal antar blok besar `space-y-8`, dalam panel `p-5`. Jangan `gap-4` untuk semuanya; kerapatan yang seragam terasa seperti spreadsheet.

### 3.3 Tabel = buku besar bank
- `x-ui.table` + `x-ui.th/tr/td`. Head 10px kapital `neutral-500` di `neutral-50`, bukan bar hitam tebal.
- Sel pertama: kode mono tebal + baris kedua nama tamu (`sub`). Sel terakhir: nominal `numeric` (mono, `text-end`).
- Status = `x-ui.status` (dot + teks 11px, border tipis). Tidak ada pill penuh warna.
- Baris hover `neutral-50/70`. Tidak ada zebra kecuali tabel > 30 baris.

### 3.4 Form
- `x-ui.field`: label 11px kapital di kiri, hint di kanan pada baris yang sama, error 11px merah di bawah. Input = `.admin-input`.
- Kelompokkan maksimal 6 field per panel; beri `eyebrow` per kelompok ("Identitas tamu", "Dokumen", "Pembayaran").
- Field angka/kurs/kode pakai `font-mono` di input.
- Tombol submit **tidak** di dalam panel form; letakkan di folio kanan atau di `page-header` actions. Form panjang → tombol sticky di kanan, bukan di bawah scroll.

### 3.5 Empty state & feedback
- Tidak ada kotak abu kosong. `x-ui.empty` dengan garis pendek, judul serif, satu kalimat **yang menyebut aksi berikutnya**, tombol `secondary`.
- Info/warning = `x-ui.note` (`border-s-4`). Bukan toast warna-warni bertumpuk.

### 3.6 Uji 3 detik
Staf CS non-teknis melihat layar → tahu (a) ini halaman apa, (b) angka penting apa, (c) klik apa selanjutnya, dalam 3 detik. Kalau tidak: kurangi tombol, perbesar judul, pindahkan total ke folio.

---

## 4. Larangan keras (grep sebelum commit)

```
rounded-2xl | rounded-xl | rounded-full (selain h-1.5/h-2 dot & avatar)
shadow-lg | shadow-xl | blur- | backdrop-blur (selain header sticky)
text-white (di atas bg-neutral-0) | slate- | gray- | #D4AF37 | gold
style="color: | style="background:
ml- | mr- | pl- | pr- | text-left | text-right | border-l- | border-r-
bg-gradient | from- | to-
```
Dua `variant="primary"` dalam satu view = tolak PR.

---

## 5. Peta komponen

| Komponen | Pakai untuk | Props utama |
|---|---|---|
| `x-ui.page-header` | judul setiap halaman | `title`, `lede`, slot `eyebrow`, slot `actions` |
| `x-ui.eyebrow` | label meta kapital kecil | slot |
| `x-ui.stat` | KPI strip | `label`, `value`, `note`, `href` |
| `x-ui.panel` | semua permukaan | `eyebrow`, `title`, `flush`, slot `actions` |
| `x-ui.segment` | baris itinerari / item booking / line item | `index`, `kicker`, `title`, `meta`, `amount`, slot `actions` |
| `x-ui.folio` | docket finansial sticky kolom kanan | `eyebrow`, `title`, `ref`, `rows`, `total-label`, `total`, `footnote`, slot = tombol merah |
| `x-ui.table` / `th` / `tr` / `td` | daftar transaksi | `numeric`, `sub` |
| `x-ui.status` | status booking/pembayaran (PRD §4.6) | `status`, slot = teks dari lang |
| `x-ui.button` | semua tombol | `variant`, `href`, `type` |
| `x-ui.field` | label+input+hint+error | `label`, `for`, `hint`, `error` |
| `x-ui.note` | panel info / peringatan | `tone` |
| `x-ui.empty` | keadaan kosong | `title`, `text`, slot = tombol |
| `x-ui.money` | nominal dengan kode mata uang redup | `currency`, `amount`, `size` |

Teks label komponen selalu lewat `__()` di view pemanggil (rule.md §3). Komponen sendiri tidak berisi string UI.

---

## 6. Prompt refactor per layar

> Refactor `resources/views/<path>.blade.php` mengikuti `UI_UX_SPECIFICATION.md`. Ganti semua kartu dengan `x-ui.panel`, header dengan `x-ui.page-header`, daftar dengan `x-ui.segment` atau `x-ui.table`, badge dengan `x-ui.status`. Layout `lg:grid-cols-12` 8/4 dengan `x-ui.folio` sticky di kanan. Tepat satu `variant="primary"`. Hapus semua kelas di §4. Logical properties saja. Cek hasil di `/admin/styleguide` sebagai pembanding, lalu tes di browser.

Urutan refactor yang disarankan: `admin/dashboard` → `admin/bookings/show` → `admin/bookings/index` → `admin/payments/*` → `customer/*` (customer layout perlu ditulis ulang total: buang tema slate/gold).
