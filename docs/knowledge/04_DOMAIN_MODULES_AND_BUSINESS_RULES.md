# 04. Modul Domain dan Aturan Bisnis — Indogate

Dokumen ini merangkum aturan bisnis operasional dan logika domain yang diterapkan pada modul M1 hingga M12 berdasarkan PRD v3.1.

---

## 1. Modul Inti dan Siklus Operasional

### M1 — Autentikasi, RBAC & Multi-Cabang
- **Konteks Cabang Tunggal:** `CurrentBranch::id()` menjadi sumber kebenaran tunggal sesi aktif.
- **Peralihan Cabang:** Hanya pengguna dengan izin `branch.switch` (Super Admin) yang dapat beralih konteks cabang melalui `Admin\BranchSwitcher`.
- **Penegakan 403 Forbidden:** Rute model binding mengecualikan `BranchScope` dan menyerahkan validasi ke Gate Policy agar akses lintas cabang secara konsisten menghasilkan respons HTTP 403 (bukan 404).

### M2 — i18n, RTL & Design Token
- **Format Tanggal & Angka:** Disesuaikan berdasarkan locale aktif (`DateFormatter` dan `Money` helper).
- **Arah Tata Letak RTL Otomatis:** Saat locale `ar`, layout menyematkan `dir="rtl"` pada elemen `<html>` dan menggunakan CSS logical properties (`start-0`, `end-0`, `ps-`, `pe-`).

### M3 — Katalog & Inventori
- **Anti Tumpang Tindih Tarif (*Rate Non-Overlap*):** Komponen tarif inventori (`Rate`) divalidasi oleh aturan `RateDoesNotOverlap` agar tidak ada periode tanggal yang saling bersinggungan untuk item yang sama.
- **Pengelolaan Media Aman:** Pengunggahan gambar dikompresi menggunakan ekstensi PHP GD bawaan (kualitas 80%) tanpa dependensi eksternal, dan disimpan di direktori publik `item-media`.

### M4 — Pricing & Currency Engine
- **Kalkulasi Harga Jual Bersih:**
  $$\text{Harga Jual IDR} = \text{Biaya Modal} + (\text{Biaya Modal} \times \text{Margin Musim}) + \text{Biaya Kanal Pembayaran}$$
- **Margin Berbasis Tanggal Keberangkatan:** Persentase margin ditentukan berdasarkan tanggal keberangkatan tamu pada kalender musim (`Season`), bukan tanggal pembuatan kuotasi.
- **Pencegahan Kebocoran Margin:** Staf tanpa izin `pricing.manage` (seperti CS Admin) tidak pernah menerima data harga modal vendor maupun persentase keuntungan kotor pada payload Livewire.

### M5 — Package Builder (Penyusun Paket Dinamis)
- **Komputasi di Sisi Klien:** Builder menggunakan Alpine.js di sisi klien untuk interaksi manipulasi item tur tanpa roundtrip server, dan hanya menyinkronkan data ke Livewire saat pengguna menyimpan paket atau meminta kalkulasi PDF.
- **Skalabilitas Jumlah Tamu (Pax Scaling):** Komponen akomodasi kamar hotel dihitung tetap berdasarkan jumlah kamar, sedangkan komponen tiket, transportasi, dan aktivitas diskalakan secara proporsional:
  $$\text{Faktor Pengali} = \lceil \text{Pax} / \text{Base Pax} \rceil$$
- **Ekspor Itinerary Asinkron:** Ekspor PDF itinerary didelegasikan ke antrean latar belakang (`GeneratePackageItineraryPdf`) untuk menghindari perlambatan request web.

### M6 — Lead & Quotation (Prospek & Penawaran)
- **Penguncian Nilai Kurs (*Exchange Rate Lock*):** Kuotasi menyimpan snapshot nilai kurs pada kolom `locked_rate`. Fluktuasi kurs berikutnya tidak akan mengubah harga penawaran yang sudah terbit.
- **Tautan Penawaran Publik Mandiri:** Kuotasi dapat diakses publik melalui rute `/q/{token}` menggunakan token acak 48 karakter tanpa perlu autentikasi. Status kuotasi diverifikasi terhadap masa kedaluwarsa (`valid_until`).
- **Pencatatan Alasan Kekalahan (*Lost Reason*):** Prospek yang diubah statusnya menjadi `lost` wajib menyertakan alasan kekalahan untuk analisis performa bisnis.

### M7 — Booking & Order (Pemesanan & Tamu)
- **Konversi Otomatis:** Aksi `ConvertQuotationToBooking` mengubah kuotasi yang disetujui menjadi entitas `PackageBooking` dengan kode pemesanan unik `BK-XXXXXXXX` dan status awal `confirmed`.
- **Mesin Status Pemesanan (`BookingStateMachine`):**
  Status pemesanan hanya dapat bertransisi mengikuti jalur resmi:
  $$\text{draft} \rightarrow \text{quoted} \rightarrow \text{confirmed} \rightarrow \text{partially\_paid} \rightarrow \text{paid} \rightarrow \text{in\_progress} \rightarrow \text{completed}$$
  *(atau dialihkan ke $\text{cancelled}$ dari status mana pun sebelum completed).*
  Setiap transisi dieksekusi dalam transaksi basis data dan dicatat ke `BookingStatusHistory`.
- **Perlindungan Dokumen Paspor (UU PDP):** Nomor paspor disimpan terenkripsi di basis data, dan berkas paspor diakses secara privat via signed URL dengan batas throttle 20 request/menit serta pemblokiran unduhan massal.

### M8 — Armada & Pengemudi (Fleet Management)
- **Penegakan Preferensi Gender Tanpa Pengecualian:** Jika tamu memilih preferensi supir wanita (`driver_gender_preference = 'female'`), sistem `AssignmentService` hanya menerima penugasan pengemudi wanita berlisensi dan menolak penugasan pria secara tegas tanpa *silent fallback*.
- **Pencegahan Bentrok Jadwal (*Schedule Conflict Engine*):** Pengemudi dan kendaraan yang sedang ditugaskan pada rentang tanggal tertentu dilarang dialokasikan ke pemesanan lain. Sistem akan melempar `ScheduleConflictException`.
- **Surat Tugas Jalan Resmi:** Pengemudi dapat mencetak surat dinas operasional via signed URL `/fleet/assignments/{assignment}/duty-letter` yang mencatat log audit pencetakan.

### M9 — Keuangan & Pembayaran
- **Alur Pembayaran Bertahap:** Mendukung skema Uang Muka (*Down Payment*), Pelunasan (*Full Payment*), dan Pembayaran Bertahap (*Installment*).
- **Perlindungan Anti Self-Approval:** Staf operasional yang membuat suatu booking dilarang memverifikasi bukti transfer pembayaran untuk booking tersebut (`SelfApprovalException`).
- **Segregasi Tugas Mutlak:** CS Admin mengelola booking tetapi tidak dapat memverifikasi pembayaran; Finance Admin memverifikasi pembayaran tetapi tidak dapat memodifikasi paket atau jadwal armada.
- **Laporan Margin Sejati (*True Margin*):**
  $$\text{True Margin} = \text{Omzet Penerimaan Bersih} - \text{Biaya Transaksi (MDR)} - \text{Total Tagihan Vendor Partner}$$

### M10 — Storefront Publik
- **Sensitivitas Budaya:** Desain visual storefront bebas dari gambar minuman keras atau pakaian terbuka, menonjolkan jaminan makanan halal, privasi villa, dan fasilitas concierge keluarga.
- **Peralihan Mata Uang Tanpa Muat Ulang:** Komponen `StorefrontCurrency` menyediakan konversi real-time antara SAR, USD, dan IDR.
- **Proteksi Spam Formulir Kontak:** Formulir `/contact` dan `/leads` dilindungi oleh field honeypot (`website`) dan pembatasan laju (*rate limiter*) `throttle:10,1`.

### M11 — Laporan & Dashboard Analitik
- **Metrik Real-Time:** `DashboardMetricsService` menyajikan ringkasan omzet, utilisasi armada, dan corong konversi prospek.
- **Ekspor Data Aman:** `ReportExportService` mengalirkan ekspor CSV dengan penambahan UTF-8 BOM (`\xEF\xBB\xBF`) agar langsung terbaca rapi pada Microsoft Excel tanpa masalah karakter encoding.

### M12 — Audit, Keamanan & Hardening
- **Pencatatan Log Audit:** Mencakup seluruh kanal sensitif (`auth`, `finance`, `fleet`, `security`, `pricing`).
- **Pembersihan Data Berkala:** Perintah artisan `indogate:purge-expired` membersihkan dokumen paspor dan token penawaran kedaluwarsa secara terjadwal.
- **Hak untuk Dihapus (*Right to be Forgotten*):** Service `DataRetentionService::anonymizeCustomer` menyediakan fitur anonimisasi data identitas tamu sesuai ketentuan UU Perlindungan Data Pribadi.
