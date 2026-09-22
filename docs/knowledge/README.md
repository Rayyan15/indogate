# Dokumentasi Sistem Indogate — Panduan Sesi Pengembangan

Direktori ini (`docs/knowledge/`) dibuat sebagai sumber kebenaran dan rujukan teknis cepat untuk sesi-sesi pengembangan berikutnya. Dokumentasi ini merangkum seluruh pemahaman komprehensif atas arsitektur, domain bisnis, basis data, antarmuka, keamanan, dan aturan operasional platform **Indogate Travel Platform**.

---

## Daftar Berkas Dokumentasi

1. **[01. Gambaran Umum Proyek](01_PROJECT_OVERVIEW.md)**
   - Visi platform, latar belakang bisnis, profil perusahaan (PT Indo International Gate & Raynad Digital).
   - Target persona (wisatawan keluarga GCC / Timur Tengah kelas atas).
   - Matriks masalah dan solusi operasional (P1 hingga P8).
   - Multi-cabang operasional (Cabang Bali & Cabang Jakarta).

2. **[02. Arsitektur dan Tumpukan Teknologi](02_ARCHITECTURE_AND_STACK.md)**
   - Tumpukan teknologi aktual (Laravel 12, Livewire 4, Alpine.js, Tailwind CSS 3, DomPDF).
   - Struktur dual-model: `App\Domain\*\Models` (domain-driven modern) vs `App\Models` (MVP/kompatibilitas).
   - Arsitektur routing ber-prefix `{locale}` dan middleware pipeline (`SetLocale`, `SetActiveBranch`, `EnsureLocaleUrlDefault`).
   - Autentikasi headless Fortify, otorisasi Spatie Permission, dan segregasi tugas (SoD).

3. **[03. Basis Data dan Model Data](03_DATABASE_AND_DATA_MODEL.md)**
   - Peta lengkap 38 tabel basis data dan relasinya.
   - Pola multi-cabang (`BelongsToBranch` trait & `BranchScope`).
   - Pola presisi moneter: representasi uang tanpa float (`BIGINT` minor units dan integer basis points).
   - Atribut terenkripsi (UU PDP) dan atribut multibahasa (`spatie/laravel-translatable`).
   - Saluran seeder utama dan seeder demo.

4. **[04. Modul Domain dan Aturan Bisnis](04_DOMAIN_MODULES_AND_BUSINESS_RULES.md)**
   - Rincian implementasi 12 modul PRD (M1 hingga M12).
   - Mesin status pemesanan (`BookingStateMachine`) dan transisi wajib via transaksi basis data.
   - Aturan anti-self-approval pada verifikasi pembayaran dan pencegahan bentrok armada (`AssignmentService`).
   - Mesin kalkulasi harga (*Pricing Engine*), penguncian kurs (*locked exchange rate*), dan rumus penentuan margin riil.

5. **[05. Frontend dan Sistem Desain UI/UX](05_FRONTEND_AND_UI_DESIGN_SYSTEM.md)**
   - Filosofi *Indogate Editorial System* (anti template generik / "AI slop").
   - Tata letak asimetris Grid 8/4 dan aturan tepat satu tombol primer merah (`#C41230`) per layar.
   - Tipografi kontras tinggi (*Fraunces* serif + *JetBrains Mono* tabular numbers + font Arab LTR/RTL).
   - Pustaka 19 komponen Blade UI (`<x-ui.*>`) dan aturan strict CSS logical properties (`ms-`, `me-`, `ps-`, `pe-`).

6. **[06. Keamanan, Pengujian, dan Operasional](06_SECURITY_TESTING_AND_OPERATIONS.md)**
   - Kepatuhan UU PDP No. 27/2022 (Right to be forgotten, sanitasi dokumen paspor).
   - Penyimpanan privat dan akses berkas via temporary signed URL bertenggang waktu.
   - Klasifikasi insiden keamanan P1–P3 dan prosedur tanggap darurat 5 tahap.
   - Struktur test suite (262 pengujian, 800 assertion lulus 100%).
   - Jadwal scheduler artisan dan konfigurasi environment.

7. **[07. Laporan Audit Potensi Bug, Resolusi, dan Risiko Sistem](07_BUG_AUDIT_AND_POTENTIAL_RISKS.md)**
   - Hasil audit mendalam swarm agent terhadap backend domain, CRUD, Livewire, keamanan, dan CI/CD.
   - Analisis dan status resolusi 7 bug kritis (multiplikasi 100x kurs valas, crash query JSON di MySQL 8.0, kebocoran cross-tenant query string, kehilangan data itinerary Alpine.js, dll).
   - Daftar celah otorisasi Livewire, validasi exists tanpa scope, dan silent validation failures.
   - Temuan dan resolusi audit pasca-perbaikan (Post-Fix Swarm Verification): Super Admin scoping, kalkulasi valas balik SAR/IDR, graceful handling keranjang belanja, isolasi `wire:target`, dan verifikasi 269 test otomatis lulus.

---

## Petunjuk untuk Sesi Berikutnya

- **Klarifikasi Stack:** Repositori ini **bukan** Vue / Inertia / Flutter. Seluruh antarmuka admin, pelanggan, dan storefront dibangun menggunakan **Laravel 12 + Blade + Livewire 4 + Alpine.js + Tailwind CSS 3**.
- **Kondisi Modul:** Seluruh modul inti M1 hingga M12 sudah terimplementasi dan lulus uji otomatis. Pengujian regresi dilakukan dengan menjalankan `php artisan test` (memakai koneksi SQLite in-memory).
- **Integritas Multi-Branch:** Akses URL lintas cabang harus menghasilkan status **HTTP 403 Forbidden**, bukan 404. Ini ditegakkan melalui Route Model Binding `withoutGlobalScope(BranchScope::class)` yang diteruskan ke Gate Policy.
