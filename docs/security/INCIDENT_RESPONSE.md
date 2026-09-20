# Prosedur Tanggap Insiden Keamanan Sistem — Indogate

Dokumen ini memuat protokol standar penanganan insiden keamanan siber, kebocoran data pribadi (UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi / UU PDP), serta langkah mitigasi darurat pada platform Indogate.

---

## 1. Klasifikasi Insiden

| Tingkat | Kriteria | Contoh Kejadian | Waktu Respon Maksimal |
|---|---|---|---|
| **P1 — Kritis** | Ancaman langsung terhadap integritas data keuangan, kebocoran data paspor/identitas massal, akses root/server tidak sah, kelumpuhan total layanan. | Kebocoran berkas paspor tamu, kompromi akun Super Admin, manipulasi saldo/pembayaran bank. | < 15 Menit |
| **P2 — Mayor** | Akses tidak sah lokal, kegagalan isolasi cabang (data cross-branch terbaca), serangan brute-force login persisten melewati mitigasi normal. | CS Cabang Jakarta mengakses booking Cabang Bali, serangan credential stuffing massal. | < 1 Jam |
| **P3 — Minor** | Upaya exploit yang berhasil dicegah WAF/rate-limiter, kegagalan log non-kritis, anomali aktivitas pengguna tunggal tanpa dampak data. | Rate-limit lockout berulang pada 1 IP, kesalahan permission ringan tanpa eksfiltrasi data. | < 4 Jam |

---

## 2. Alur Tanggap Insiden (5 Tahap)

### Tahap 1: Deteksi & Eskalasi (Identification)
1. Notifikasi otomatis terpicu dari:
   - Audit Log anomali (`activity('security')`).
   - Rate limit lockout berulang (`Authentication rate limit lockout triggered`).
   - Error log 403 berulang dari signed URL atau isolasi cabang.
2. Tim teknis mengklasifikasikan tingkat keparahan (P1/P2/P3).
3. Pembentukan tim tanggap darurat (*Incident Response Team*): Tech Lead, Security Officer, dan PIC Indogate.

### Tahap 2: Pembatasan & Isolasi (Containment)
Langkah darurat teknis untuk memutus penyebaran:
1. **Pencabutan Sesi Pengguna**:
   - Jika akun staf atau admin terkompromi, nonaktifkan akun segera via database/UI (`is_active = false`).
   - Hapus seluruh sesi aktif dari cache/tabel sesi:
     ```bash
     php artisan session:table-clear # atau flush session redis/file
     ```
2. **Pembatasan Jaringan & Firewall**:
   - Blokir alamat IP penyerang pada tingkat web server (Nginx/Apache/Cloudflare).
3. **Kunci Mode Pemeliharaan (Jika P1)**:
   ```bash
   php artisan down --secret="indogate-emergency-bypass"
   ```

### Tahap 3: Investigasi Forensik & Pembersihan (Eradication)
1. **Analisis Jejak Audit**:
   - Buka `/admin/security/audit-logs`.
   - Filter kanal `auth` dan `security` berdasarkan rentang waktu dan alamat IP tersangka.
   - Periksa tabel `activity_log` untuk melihat daftar subjek dan rekaman yang tersentuh.
2. **Pemeriksaan Kerentanan**:
   - Identifikasi celah masuk (injeksi, token bocor, sesi usang).
   - Periksa integritas kode dengan `git status` dan `git diff`.
3. **Rotasi Kunci Keamanan**:
   - Jika `APP_KEY` terindikasi bocor:
     ```bash
     php artisan key:generate
     ```
   - Rotasi kata sandi database dan kredensial API perbankan/vendor.

### Tahap 4: Pemulihan Layanan (Recovery)
1. **Verifikasi Integritas Data**:
   - Jalankan uji mandiri konsistensi data finansial:
     ```bash
     php artisan test --filter=Finance
     ```
2. **Pemulihan dari Backup (Jika Terjadi Kerusakan/Ransomware)**:
   - Jalankan restorasi cadangan data terverifikasi (`storage/app/backups/`).
3. **Uji Fungsional & Normalisasi**:
   - Buka mode pemeliharaan dan pantau traffic secara ketat selama 24 jam pertama:
     ```bash
     php artisan up
     ```

### Tahap 5: Pelaporan & Pasca-Insiden (Post-Incident Review & UU PDP)
1. **Kepatuhan UU PDP No. 27/2022**:
   - Berdasarkan Pasal 46 UU PDP, jika terjadi kegagalan perlindungan data pribadi (misal: nomor paspor atau kontak tamu bocor), Indogate **wajib menyampaikan pemberitahuan tertulis paling lambat 3 x 24 jam (72 jam)** kepada:
     - Lembaga Pengawas Perlindungan Data Pribadi resmi.
     - Subjek Data Pribadi (tamu/wisatawan yang terdampak).
   - Pemberitahuan mencakup: data pribadi yang terungkap, waktu dan kronologi insiden, serta langkah penanganan dan narahubung resmi.
2. **Laporan Investigasi Internal**:
   - Dokumentasikan *Root Cause Analysis* (RCA).
   - Tambahkan skenario pengujian otomatis baru di test suite untuk menjamin celah yang sama tidak pernah berulang.
