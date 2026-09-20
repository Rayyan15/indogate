# Progress Modul M12 — Audit, Keamanan & Hardening

**Status:** Selesai (100% verified via automated tests & real browser)  
**Referensi:** `docs/PRD_Indogate_v3.1.md` §M12

---

## 1. Ruang Lingkup & Kebutuhan Bisnis

Modul M12 memastikan sistem memenuhi standar keamanan siber, enkripsi data pribadi, pembatasan laju (rate limiting), kebijakan retensi dokumen, prosedur tanggap insiden, serta kepatuhan hukum **UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)** sebelum platform dioperasikan dengan data transaksi nyata.

### Komponen Kunci

1. **Kelengkapan Activity Log di Seluruh Aksi Sensitif**:
   - **Autentikasi (`app/Listeners/LogAuthenticationActivity.php`)**:
     - `Login` $\rightarrow$ mencatat user, IP, dan User Agent ke kanal `auth`.
     - `Logout` $\rightarrow$ mencatat user dan IP ke kanal `auth`.
     - `Failed` $\rightarrow$ mencatat percobaan login gagal beserta email tersangka dan IP ke kanal `auth`.
     - `Lockout` $\rightarrow$ mencatat pemicuan rate limit brute-force ke kanal `security`.
   - **Keuangan (`PaymentService`)**:
     - Pencatatan pembayaran, verifikasi pembayaran, penolakan dengan alasan, dan pemrosesan refund ke kanal `finance`.
   - **Armada & Driver (`AssignmentService`)**:
     - Penugasan driver, pembatalan tugas dengan alasan wajib, dan akses surat tugas ke kanal `fleet`.
   - **Administrasi Pengguna (`UserForm`)**:
     - Pembuatan dan perubahan user, penetapan role, dan perubahan status aktif ke kanal `security`.
   - **Tata Kelola Data (`DataRetentionService`)**:
     - Pembersihan dokumen paspor kedaluwarsa dan penghapusan/anonimisasi data pribadi tamu (UU PDP) ke kanal `security`.

2. **Halaman Penelusuran Activity Log (Audit Trail)**:
   - Rute: `/admin/security/audit-logs` dengan penegakan izin `activitylog.view`.
   - Komponen Livewire: `App\Livewire\Admin\Security\AuditLogList`.
   - Filter reaktif: Modul/Kanal (`auth`, `finance`, `fleet`, `security`, `pricing`), Aktor Pengguna, Rentang Tanggal, dan Pencarian Teks bebas pada deskripsi/properti/subjek.
   - Modal interaktif untuk memeriksa metadata dan perubahan data (JSON formatted).
   - Terjemahan multibahasa lengkap (ID, EN, AR) dengan layout RTL otomatis untuk Bahasa Arab.

3. **Enkripsi Menyeluruh Data Sensitif (Encryption at Rest)**:
   - Kolom `passport_number` pada tabel `customers` dan `booking_guests` terenkripsi di tingkat database menggunakan enkripsi simetris Laravel (`'encrypted'` cast).
   - Terbukti pada tingkat database mentah (`DB::table(...)`) bahwa data tersimpan dalam bentuk ciphertext aman dan tidak terbaca teks biasa.

4. **Proteksi Berkas Privat via Signed URL**:
   - Seluruh berkas privat dilindungi signed URL berbatas waktu:
     - Dokumen paspor tamu: `admin.package-bookings.guests.passport-download` (`signed`, `throttle:20,1`).
     - Bukti bayar transfer: `admin.finance.proofs.download` (`signed`).
     - Surat tugas dinas: `admin.fleet.assignments.duty-letter` (`signed`).
   - Akses langsung tanpa tanda tangan sah ditolak secara tegas dengan HTTP 403 Forbidden.

5. **Pembatasan Laju (Rate Limiting & Anti Brute-Force)**:
   - Endpoint login membatasi maksimal 5 percobaan gagal berturut-turut sebelum memicu lockout berbatas waktu.
   - Endpoint formulir prospek publik (`POST /leads`) dibatasi maksimal 10 permintaan per menit (`throttle:10,1`).

6. **Kebijakan Retensi & UU PDP (Right to be Forgotten)**:
   - `DataRetentionService::purgeExpiredGuestDocuments($days = 90)`: Menghapus berkas fisik paspor dari storage lokal privat dan mengosongkan nomor identitas untuk pemesanan yang telah selesai/dibatalkan melampaui batas retensi.
   - Perintah terjadwal Artisan: `php artisan security:purge-expired-data --days=90`.
   - `DataRetentionService::anonymizeCustomer($customer)`: Anonimisasi nama, pengosongan paspor dan preferensi, serta penonaktifan akun user atas permintaan tamu (UU PDP) tanpa merusak audit integritas transaksi keuangan historis.

7. **Backup Otomatis & Pemulihan**:
   - Perintah Artisan: `php artisan indogate:backup {--destination=}` untuk mencadangkan database dengan validasi integritas file.

8. **Audit Ketergantungan (Dependency Audit)**:
   - `composer audit`: 0 security vulnerability advisories found.
   - `npm audit`: 0 vulnerabilities.

9. **Dokumen Tanggap Insiden**:
   - `docs/security/INCIDENT_RESPONSE.md`: Protokol 5 tahap tanggap insiden (P1, P2, P3), runbook darurat teknis, dan prosedur wajib pemberitahuan 72 jam sesuai UU PDP No. 27 Tahun 2022.

---

## 2. Hasil Pengujian Otomatis (PHPUnit)

Seluruh 19 pengujian keamanan otomatis pada `tests/Feature/Security/` lulus 100%:

| Berkas Uji | Kasus yang Diuji | Status |
|---|---|---|
| `ActivityLogCoverageTest.php` | Auth events (login, logout, failed, lockout), aksi finansial (record, verify, refund), aksi penugasan armada | Lulus |
| `AuditLogViewerTest.php` | Akses Super Admin, blokir pengguna tanpa izin, filter kanal/pencarian, modal inspeksi | Lulus |
| `AuthorizationSweepTest.php` | Tamu belum login diarahkan ke login, Customer role ditolak 403 dari seluruh rute admin, Super Admin diizinkan 200 | Lulus |
| `BackupRecoveryTest.php` | Perintah backup menghasilkan berkas valid tidak kosong, activity log tercatat | Lulus |
| `DataRetentionTest.php` | Berkas paspor lampau terhapus, paspor pemesanan aktif tetap aman, anonimisasi tamu UU PDP | Lulus |
| `EncryptionTest.php` | Kolom sensitif Customer dan BookingGuest tersimpan sebagai ciphertext di database mentah | Lulus |
| `SecurityRateLimitTest.php` | 5x gagal login memicu lockout, formulir lead publik dibatasi 10 req/menit (HTTP 429) | Lulus |
| `SignedUrlTest.php` | Berkas privat paspor, bukti bayar, dan surat tugas menolak URL tanpa signed (403), menerima signed URL sah | Lulus |

---

## 3. Hasil Pengujian Real Browser (Puppeteer / Edge)

Dijalankan langsung melalui `test-security-browser.js` pada `http://127.0.0.1:8000`:
- **Test 1 (Akses Halaman Audit Log)**: Super Admin membuka `/id/admin/security/audit-logs`, tabel log dan filter termuat sempurna.
- **Test 2 (Reaktivitas Filter Kanal)**: Mengubah filter ke `auth`, tabel bereaksi memuat log autentikasi.
- **Test 3 (i18n & Tata Letak RTL Arab)**: Membuka `/ar/admin/security/audit-logs`, atribut `dir="rtl"` terverifikasi aktif dan judul dalam Bahasa Arab tampil presisi.
- **Test 4 (Penegakan Otorisasi RBAC)**: CS Admin tanpa izin `activitylog.view` ditolak dengan HTTP 403 Forbidden.

Status: **ALL M12 BROWSER TESTS COMPLETED SUCCESSFULLY 100%**.
