# Enhancement — Sidebar & Topbar Admin

**Status:** Selesai (fungsional), verifikasi visual mobile **belum lengkap** · **Tanggal:** 2026-09-17 · Bukan modul PRD bernomor — enhancement UI ad-hoc atas permintaan owner di luar M1-M3.

## Ringkasan

Sidebar admin (`resources/views/layouts/admin.blade.php`) tadinya flat: semua grup menu selalu terbuka, lebar tetap 256px, tidak ada dukungan mobile (di layar sempit sidebar akan menutupi konten).

## Yang dibangun

- **Collapse/expand per grup menu** (Operations, Inventory, Configuration, Administration) — state per-grup disimpan di `localStorage`, otomatis terbuka kalau ada halaman aktif di dalamnya. Komponen baru: `resources/views/components/ui/nav-group.blade.php`.
- **Collapse/expand seluruh sidebar** jadi mode rail icon-only, tombol chevron di sebelah logo, state disimpan di `localStorage`.
- **Sidebar responsif mobile** — di bawah breakpoint `lg` (1024px), sidebar jadi overlay geser dari sisi kiri/kanan (logical `start-0`, aman RTL) dengan backdrop gelap, dibuka lewat tombol hamburger di topbar.
- **Topbar**: judul halaman dibikin tebal + kapital + icon (icon otomatis dipilih dari nama route, tidak perlu ubah tiap file view), elemen sekunder (View Site, locale switcher, branch switcher, badge role) disembunyikan bertahap di layar sempit (`hidden md:flex`, dst) biar tidak numpuk.
- **Grup "Inventory" dan "Catalog" digabung** — sebelumnya dua blok terpisah yang sama-sama digerbangi `catalog.manage`, sekarang satu grup logis.
- **Badge jumlah pending payment** di menu Payments, query langsung (branch-scoped otomatis lewat `BelongsToBranch`), cuma tampil untuk user yang punya `payment.verify`.
- **Tanda panah kecil di pojok kanan** untuk kartu yang bisa diklik (`x-ui.stat` dan `x-ui.panel` yang diberi prop `href`) — sinyal visual bahwa elemen itu actionable.

## Belum diverifikasi (dicatat, bukan diklaim beres)

**Tampilan asli di orientasi potret/lansekap HP dan tablet belum sempat dilihat langsung.** Alat browser automation di sesi ini (`resize_window`) tidak berhasil mengubah viewport render sungguhan — dicoba dua kali ke 390×844, `window.innerWidth` tetap terbaca ukuran layar remote (1536px). Jadi tidak ada bukti screenshot mode sempit yang bisa dipertanggungjawabkan.

Yang sudah dipastikan lewat baca kode (bukan lihat langsung):
- Semua breakpoint pakai lebar (`sm:`/`md:`/`lg:`), bukan `@media (orientation:...)` eksplisit — pendekatan ini otomatis menyesuaikan baik untuk HP tegak maupun HP miring karena breakpoint bereaksi ke lebar viewport yang tersedia, bukan label orientasi.
- Modal (`x-modal`) sudah scroll aman untuk viewport pendek (kasus landscape HP ~400px tinggi): wrapper terluar `overflow-y-auto`, form panjang tidak akan terpotong.
- Sidebar overlay mobile `h-screen overflow-y-auto` — aman discroll sendiri kalau konten lebih tinggi dari layar.

**Tindak lanjut yang disarankan:** uji manual di perangkat/DevTools sungguhan (toggle device toolbar, putar orientasi) sebelum modul ini dianggap 100% selesai untuk DoD "responsive ke semua resolusi". Kalau ketemu bagian yang kepotong/numpuk, laporkan spesifik untuk diperbaiki.

Re-cek 2026-09-18 (audit gap pra-M6): logic re-diverifikasi baca kode sekali lagi — `admin.blade.php` overlay pakai `-translate-x-full` (default, di bawah `lg`) → `translate-x-0` (`mobileOpen=true`), backdrop `lg:hidden`, RTL-aware (`rtl:translate-x-full`). Pola standard overlay-sidebar, tidak ada red flag. Batasan tool browser automation di environment ini (viewport render tidak berubah sungguhan) sama seperti sebelumnya — tidak dicoba ulang ketiga kali karena hasilnya sudah diketahui gagal dengan cara yang sama. Kesimpulan tidak berubah: perlu uji perangkat sungguhan sebelum modul ini 100% dianggap selesai untuk DoD responsive.

## Hasil pengujian

- 64 test tetap PASS, 0 regresi.
- Pint bersih.
- Verifikasi fungsional di browser desktop (lebar 1536px): expand/collapse per grup jalan, collapse seluruh sidebar ke mode icon jalan, arrow indicator di stat card muncul.
- **Tidak** ada verifikasi visual di lebar sempit/mobile — lihat bagian "Belum diverifikasi" di atas.
