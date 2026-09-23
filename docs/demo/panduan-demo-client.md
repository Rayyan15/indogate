# Panduan Demo Indogate untuk Client

Durasi yang disarankan: 30–40 menit demo + 15 menit tanya jawab.
Audiens: travel agency / tour operator skala internasional (pasar Timur Tengah, Asia, Eropa → Indonesia).

---

## 1. Pesan utama (sampaikan di 2 menit pertama)

> "Indogate adalah satu sistem untuk seluruh bisnis tur Anda ke Indonesia: dari calon tamu pertama kali bertanya lewat WhatsApp, sampai sopir menjemput mereka di bandara, dan laporan margin sebenarnya di akhir bulan. Multi-cabang, multi-bahasa (termasuk Arab), dan multi-mata uang sejak hari pertama."

Tiga alasan kenapa mereka butuh ini:

1. **Jual dalam mata uang tamu, hitung dalam rupiah.** Harga tampil dalam SAR/USD/IDR dengan kurs yang dikunci saat penawaran dibuat. Tidak ada lagi kerugian karena kurs bergerak antara quote dan pembayaran.
2. **Margin terlihat nyata, bukan tebakan.** Setiap paket dihitung otomatis: biaya vendor + margin + biaya kanal pembayaran. Laporan margin menunjukkan untung bersih per booking setelah biaya vendor dan MDR.
3. **Kontrol dan keamanan untuk operasi lintas negara.** Data per cabang terpisah, pembuat booking tidak boleh memverifikasi pembayarannya sendiri, semua aksi tercatat di audit log, dokumen paspor hanya bisa diunduh lewat link aman.

---

## 2. Alur demo (urutan yang disarankan)

Siapkan dua tab: **Admin** (sudah login Super Admin) dan **Storefront publik** (`/ar` atau `/en`).

### Babak 1 — Wajah ke tamu (5 menit)
1. Buka storefront `/ar`. Tunjukkan **tampilan RTL bahasa Arab penuh**, lalu ganti ke EN dan ID.
2. Buka katalog paket, ganti mata uang **SAR → USD → IDR**. Harga berubah langsung.
3. Buka detail paket, tunjukkan tombol WhatsApp dan form minat (lead).

**Kalimat kunci:** "Tamu dari Riyadh melihat harga dalam Riyal, dalam bahasa Arab, tanpa Anda membuat website terpisah."

### Babak 2 — Menyusun paket (7 menit)
1. Admin → **Paket** → buka paket (mis. "Liburan Bali" / "Bali Escape").
2. Tunjukkan komponen (hotel, kendaraan), klik **Hitung Ulang**: ringkasan harga, total modal, nilai margin muncul otomatis.
3. Ganti **mata uang tampilan** dan **jumlah tamu** → harga menyesuaikan.
4. Tunjukkan toggle **"Tampilkan di storefront"** (paket draft vs publik) dan ekspor **itinerary PDF** dalam EN/ID/AR.

**Kalimat kunci:** "Tim sales tidak menghitung manual di Excel lagi. Margin dan biaya kanal pembayaran sudah termasuk."

### Babak 3 — Lead → Penawaran → Booking (8 menit)
1. Admin → **Lead** → tunjukkan papan kanban (baru, dihubungi, ditawarkan, menang, kalah).
2. Buka satu lead → buat **penawaran** → salin **link penawaran publik** (tanpa login, bisa dikirim via WhatsApp).
3. Buka link itu di tab lain: tamu melihat penawaran dalam bahasanya, dengan kurs yang dikunci.
4. Kembali ke admin → **konversi penawaran jadi booking**. Status lead otomatis menjadi "Menang".

**Kalimat kunci:** "Satu klik dari penawaran ke booking, dan funnel penjualan Anda terukur."

### Babak 4 — Operasional (6 menit)
1. Admin → **Pemesanan** → buka booking: data tamu, catatan, riwayat status.
2. Tunjukkan **preferensi gender sopir** (penting untuk tamu Timur Tengah) dan **penugasan sopir/kendaraan** di kalender armada.
3. Tunjukkan **voucher** booking dalam 3 bahasa.

**Kalimat kunci:** "Tamu keluarga dari Saudi bisa minta sopir perempuan, dan sistem memastikan jadwal sopir tidak bentrok."

### Babak 5 — Keuangan & kontrol (7 menit)
1. Di booking → **Catat Pembayaran** (DP) dalam SAR/USD → masuk sebagai *pending*.
2. Login/beralih ke Finance → **Pembayaran Masuk** → verifikasi. Tunjukkan aturan: *orang yang mencatat tidak boleh memverifikasi sendiri*.
3. Tunjukkan status booking berubah otomatis (DP → Lunas), **Piutang (AR)**, **Laporan Margin**, dan **Faktur/Kuitansi** PDF.
4. Tunjukkan **refund** dengan alasan wajib.
5. Buka **Dashboard Eksekutif**: pendapatan, margin bersih, tingkat konversi, filter per cabang & periode (mengikuti zona waktu cabang: WITA untuk Bali, WIB untuk Jakarta).
6. Tunjukkan **Log Audit**: siapa melakukan apa, kapan.

**Kalimat kunci:** "Direktur keuangan Anda bisa melihat margin nyata per cabang tanpa menunggu tutup buku."

### Penutup (2 menit)
Ringkas 3 alasan di bagian 1, lalu masuk ke roadmap pembayaran online (bagian 4).

---

## 3. Checklist sebelum demo (lakukan 30 menit sebelumnya)

- [ ] `php artisan serve` jalan, `php artisan queue:work` jalan (untuk PDF voucher/itinerary dan email).
- [ ] Unpublish paket tes ("Tes 2", "Tes Redirect") lewat toggle "Tampilkan di storefront".
- [ ] Pastikan ada kurs **SAR** dan **USD** yang aktif (Konfigurasi → Kurs). Tanpa kurs, sistem sekarang sengaja menolak transaksi mata uang asing (bukan diam-diam memakai kurs 1).
- [ ] Siapkan 1 lead "bersih" untuk alur penawaran → booking.
- [ ] Siapkan akun kedua (Finance) untuk demo verifikasi (aturan pemisahan tugas).
- [ ] Hapus data tes jika tidak perlu: hotel "QA Bugfix Hotel", storefront booking #1.

**Hindari saat demo (masih dalam perbaikan):**
- Dua admin meng-assign sopir yang sama bersamaan.
- Laporan dengan periode "Semua Waktu" pada data besar (bisa lambat).
- Filter/search katalog publik (teks dropdown belum terbaca — area UI tim desain).
- Harga kartu paket di katalog yang tampil sama semua (cek data harga mulai).

---

## 4. Saran Payment Gateway (IDR / USD / SAR)

### Kondisi saat ini
Sistem sudah punya lapisan abstraksi `PaymentProviderInterface` dengan satu implementasi, **Manual Transfer**. Artinya gateway online bisa ditambahkan sebagai provider baru **tanpa mengubah aturan bisnis** (verifikasi, refund, kurs, laporan margin tetap sama). Ini poin jual: "siap untuk pembayaran online, tinggal pilih mitra".

### Kebutuhan
- Menerima **IDR** (tamu domestik / agen lokal), **USD** (internasional), **SAR** (Saudi, termasuk kartu **mada** yang dominan di Saudi).
- Settlement ke rekening perusahaan Indonesia (dan/atau entitas luar negeri jika ada).
- Refund parsial, webhook, dan hosted checkout (agar tidak perlu sertifikasi PCI-DSS penuh).

### Rekomendasi: dua jalur, satu interface

| Kebutuhan | Kandidat | Kenapa |
|---|---|---|
| **Domestik IDR + kartu internasional** | **Xendit** (alternatif: Midtrans, DOKU) | Berbasis Indonesia, Virtual Account, QRIS, e-wallet, kartu internasional; settlement IDR ke rekening lokal; dokumentasi API dan webhook matang. |
| **Pasar Saudi (SAR, mada, Apple Pay)** | **Tap Payments**, **HyperPay**, atau **Checkout.com** | Mendukung **mada** dan SAR secara native; tamu Saudi jauh lebih mudah membayar dengan metode lokal. |
| **Opsi satu vendor global (volume besar)** | **Adyen** atau **Checkout.com** | Satu kontrak untuk banyak negara/mata uang termasuk IDR, USD, SAR; cocok jika volume transaksi sudah besar (biasanya ada minimum volume). |

**Saran bertahap:**
1. **Tahap 1:** Xendit untuk IDR + kartu internasional (USD). Cepat diintegrasikan, cocok dengan entitas Indonesia.
2. **Tahap 2:** Tambah Tap/HyperPay untuk SAR + mada saat volume dari Saudi signifikan.
3. **Tahap 3:** Evaluasi konsolidasi ke Adyen/Checkout.com bila volume lintas negara besar dan ingin satu laporan rekonsiliasi.

**Catatan penting saat memilih:**
- **Stripe** tidak menerima merchant berbadan hukum Indonesia secara langsung (perlu entitas di negara yang didukung, mis. Singapura). Pertimbangkan hanya jika perusahaan punya entitas luar negeri.
- Tanyakan ke setiap vendor: dukungan **multi-currency pricing vs settlement currency**, biaya konversi (FX markup), MDR per metode, waktu settlement, refund parsial, dan sandbox untuk uji.
- Ketentuan dan ketersediaan vendor berubah; **verifikasi syarat terbaru** langsung ke vendor sebelum memutuskan.

### Yang perlu dibangun saat integrasi (estimasi teknis)
- Provider baru (mis. `XenditProvider`) yang mengimplementasikan `PaymentProviderInterface` (create intent → hosted checkout, verify via webhook, refund).
- Endpoint **webhook** dengan verifikasi signature dan **idempotency** (pembayaran yang sama tidak diproses dua kali — mekanisme lock dan guard status sudah ada di `PaymentService`).
- Pemetaan biaya kanal (MDR) vendor ke tabel **Biaya Kanal Pembayaran** agar laporan margin tetap akurat.
- Halaman bayar untuk customer di storefront (menggantikan upload bukti transfer).

---

## 5. Antisipasi pertanyaan client

- **"Bisa pakai domain/brand kami sendiri?"** Bisa; storefront terpisah dari panel admin.
- **"Data kami aman?"** Data per cabang terisolasi, akses berbasis peran, audit log, dokumen paspor dengan link bertanda tangan dan batas unduh, siap 2FA untuk admin.
- **"Bisa tambah bahasa/mata uang?"** Mata uang dan kurs dikelola dari panel; bahasa mengikuti struktur file terjemahan (EN/ID/AR sudah ada).
- **"Bisa integrasi pembayaran online?"** Ya, arsitektur sudah disiapkan (bagian 4).
