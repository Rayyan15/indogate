# 01. Gambaran Umum Proyek — Indogate Travel Platform

---

## 1. Visi dan Misi Produk
**Indogate** (*PT Indo International Gate*, dikembangkan bersama *Raynad Digital*) adalah platform manajemen perjalanan wisata *inbound* mewah (*bespoke luxury travel platform*) multi-cabang. Platform ini mengotomatisasi dan memprofesionalkan seluruh rantai operasional biro perjalanan kelas atas di Indonesia—mulai dari penerimaan prospek (*lead capture*), penyusunan paket tur dinamis (*dynamic packaging*), manajemen kurs & biaya transaksi lintas negara, penerbitan kuotasi resmi terkunci, manajemen pemesanan & data tamu berenkripsi (sesuai UU Perlindungan Data Pribadi No. 27/2022), penugasan armada & supir dengan kriteria khusus, hingga rekonsiliasi keuangan bertahap dan etalase publik (*storefront*).

---

## 2. Target Pengguna dan Karakteristik Tamu

### 2.1 Wisatawan Eksternal (High Net-Worth Individuals)
- **Fokus Geografis Utama:** Tamu keluarga dari kawasan **Dewan Kerja Sama Teluk (GCC / Gulf Cooperation Council: Arab Saudi, UEA, Qatar, Kuwait, Oman, Bahrain)** yang mengunjungi destinasi utama seperti Bali dan Jakarta.
- **Karakteristik & Kebutuhan Kultural:**
  - Kebutuhan privasi keluarga yang sangat ketat (kolam renang villa privat tertutup).
  - Jaminan makanan 100% halal dan kenyamanan ibadah.
  - Preferensi supir spesifik: opsi pengemudi wanita berlisensi untuk keluarga Muslim tanpa mahram pria, atau supir yang fasih berbahasa Arab/Inggris.
  - Kemudahan bertransaksi dalam mata uang asing (SAR, USD) dengan harga transparan dan konversi kurs yang adil.

### 2.2 Pengguna Internal Sistem
- **Super Admin (Direksi & Pemilik Bisnis):** Memiliki akses penuh lintas cabang, wewenang atas kebijakan penetapan margin keuntungan dasar, peralihan cabang aktif, manajemen pengguna, dan peninjauan log audit menyeluruh.
- **CS Admin (Customer Service & Operasional):** Menangani interaksi harian tamu, input prospek, perancangan paket tur dinamis (*Package Builder*), penerbitan kuotasi resmi, pendaftaran manifest tamu & dokumen paspor, serta penugasan armada & pengemudi. CS Admin dibatasi agar tidak dapat melihat margin riil dan tidak dapat memverifikasi pembayaran.
- **Finance Admin (Keuangan & Akuntansi):** Bertanggung jawab atas pembaruan kurs valuta asing, verifikasi bukti transfer pembayaran tamu (DP & pelunasan), pencatatan pembayaran ke mitra vendor (hotel, armada, pemandu), pemantauan piutang, dan analisis laporan laba-rugi sejati (*true margin*). Finance Admin tidak memiliki wewenang mengubah data operasional paket maupun memanipulasi jadwal armada.
- **Pelanggan Publik (Customer):** Mengakses storefront mewah untuk melihat paket wisata terbitan, menghitung simulasi harga dalam SAR/USD/IDR, serta mengajukan inquiry/kontak langsung melalui form kontak terproteksi atau tautan WhatsApp.

---

## 3. Matriks Masalah dan Solusi Sistem (P1–P8)

| Kode | Masalah Nyata di Lapangan | Solusi yang Diterapkan di Indogate |
|---|---|---|
| **P1** | Kalkulasi harga manual di spreadsheet rawan salah kutip dan kebocoran margin keuntungan. | **Pricing & Currency Engine:** Kalkulasi berbasis integer basis points dengan aturan margin musiman otomatis per tipe produk. |
| **P2** | Penyusunan proposal tur custom memakan waktu 1–2 jam per penawaran. | **Package Builder:** Pembangun paket interaktif di sisi klien (Alpine.js + Livewire 4) dengan kalkulasi real-time tanpa roundtrip server. |
| **P3** | Prospek (lead) tercecer di pesan pribadi WhatsApp dan alasan penolakan tidak tercatat. | **Lead Pipeline & Quotation:** Pencatatan status terpusat, pengingat follow-up, kewajiban mencatat alasan kekalahan (*lost reason*), dan pembuatan tautan kuotasi ber-token acak. |
| **P4** | Penugasan supir via telepon rawan bentrok jadwal dan mengabaikan preferensi gender/bahasa tamu. | **Fleet Conflict Engine & Preference Enforcer:** Validasi bentrok jadwal armada otomatis dan pemaksaan preferensi supir wanita tanpa silent fallback. |
| **P5** | Fluktuasi kurs 3 mata uang (SAR, USD, IDR) menyebabkan kerugian selisih kurs. | **Rate-Locking Mechanism:** Nilai kurs dikunci saat penawaran diterbitkan (`locked_rate`), melindungi harga kesepakatan dari fluktuasi pasar berikutnya. |
| **P6** | Uang masuk tidak terekonsiliasi; tamu sering diberangkatkan sebelum pembayaran lunas. | **BookingStateMachine & Finance Module:** Alur pembayaran bertahap (DP vs Pelunasan) dengan rekonsiliasi ketat dan verifikasi berjenjang oleh tim Finance. |
| **P7** | Pengiriman berkas paspor di grup chat melanggar UU Perlindungan Data Pribadi (UU PDP No. 27/2022). | **Encrypted PII & Signed URLs:** Nomor paspor dienkripsi di level basis data (AES-256-CBC), berkas disimpan di storage privat lokal dengan akses terbatas melalui Temporary Signed URL bertenggang waktu. |
| **P8** | Biaya transaksi lintas negara (MDR kartu internasional 5-6%) menggerus laba perusahaan. | **Channel Fee Absorption & Strategy:** Komponen MDR dihitung otomatis ke dalam simulasi harga jual, dan pembayaran pelunasan diarahkan via transfer bank untuk menekan biaya transaksi. |

---

## 4. Multi-Cabang Operasional (Cabang Bali & Cabang Jakarta)

Sistem Indogate dirancang untuk mengisolasi operasional antar-cabang:
1. **Cabang Bali (`BALI`, Zona Waktu: `Asia/Makassar` / WITA):**
   - Fokus: Operasional wisata rekreasi mewah, resort villa privat, aktivitas bahari, dan manajemen armada lokal berpreferensi khusus.
2. **Cabang Jakarta (`JKT`, Zona Waktu: `Asia/Jakarta` / WIB):**
   - Fokus: Operasional korporat, MICE, akomodasi hotel bisnis, serta koordinasi penerbangan dan gateway keuangan pusat.

Setiap transaksi, data master katalog, paket, kuotasi, pemesanan, armada, dan pembayaran terikat pada `branch_id`. Isolasi ditegakkan di lapisan basis data (`BranchScope`) dan lapisan otorisasi (menghasilkan HTTP 403 Forbidden bila diakses lintas cabang).
