# M6 — Lead & Quotation

**Status:** SELESAI · **Tanggal:** 2026-09-18 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M6

## Keputusan arsitektur (final)

1. **Snapshot = hitung sekali, persist, jangan pernah recompute.** `QuotationGenerator` panggil `PackageCalculator::calculate()` (M5, tidak diubah) sekali saat quotation dibuat, lalu simpan hasil integer ke `quotations`/`quotation_items`. Halaman publik & admin selalu baca kolom tersimpan — tidak pernah panggil `PricingEngine`/`PackageCalculator` lagi untuk quotation yang sudah ada. Ini yang bikin "kurs berubah tidak mengubah penawaran lama" (RateLockTest) otomatis benar tanpa sentuh `PricingEngine`/`Converter` sama sekali.
2. **`locked_rate`** = nilai decimal (`ExchangeRate::currentFor($currency)->rate`, fallback `1.0` untuk IDR) di-copy langsung ke kolom `quotations.locked_rate`, persis sesuai skema literal PRD (bukan FK ke `exchange_rates`).
3. **`quotation_items` cuma simpan harga jual (post-margin)** — tidak ada kolom cost/margin sama sekali di skema, jadi kebocoran margin ke tamu publik mustahil secara struktural, tidak perlu replikasi logic hiding M4/M5.
4. **Halaman quotation publik = Controller + Blade biasa, bukan Livewire.** Read-only, satu tombol WhatsApp (`wa.me` link statis, bukan API) — tidak ada state interaktif yang butuh roundtrip server.
5. **Token, bukan signed URL.** `Str::random(48)` unik, `Quotation::getRouteKeyName()` → `'token'`. Keamanan = token tak-tertebak + cek `valid_until`/`status` di controller.
6. **WhatsApp = link `wa.me/{phone}?text=...`** dengan template dari lang file sesuai `lead->locale` (bukan `app()->getLocale()`, dites eksplisit di `ExpiryTest`/blade).
7. **Expire quotation = Artisan command (`quotations:expire`) + `withSchedule`** di `bootstrap/app.php` — scheduled job pertama di project ini, tidak ada pola existing.
8. **Lead status tanpa state machine formal** (itu konsep M7) — cukup validasi manual "lost wajib isi lost_reason" di `LeadForm::save()`.
9. **`lead.manage` & `quotation.create`** — permission sudah pre-seeded sejak M1, tidak ada permission baru ditambahkan.

## Bug ditemukan & diperbaiki selama sesi ini

1. **Honeypot field publik tidak benar-benar tersembunyi.** `class="absolute -left-[9999px]"` (Tailwind arbitrary value) tidak ter-compile ke CSS akhir — honeypot render INLINE persis di sebelah field Nama (kebukti dari screenshot browser: dua kotak kosong bersisian di bawah label "Nama"). Ini bikin klik/ketik otomatis (dan berpotensi user asli di viewport sempit) salah sasaran secara tidak konsisten — submit form publik gagal diam-diam tanpa network request maupun error konsol. **Fix:** honeypot pakai inline `style="position:absolute;left:-9999px;top:-9999px;"` — gak bergantung sama sekali ke pipeline build CSS. Ditemukan & dikonfirmasi lewat 2x browser-test agent (form gagal submit) + verifikasi manual sesi ini (sebelum fix: reproduce persis; setelah fix: submit sukses, pesan "Terima kasih..." muncul).
2. **Activity log lead bocor teks alasan-kalah ke transisi status lain.** Saat CS Admin sempat isi "Alasan Kalah" lalu ubah status ke status lain (bukan "kalah") sebelum simpan, teks alasan lama ikut nempel di catatan aktivitas transisi yang gak ada hubungannya (mis. "new -> qualified: Budget tidak sesuai"). **Fix:** catatan aktivitas cuma nyertain `lost_reason` kalau status baru memang `lost`; ditambah `updatedStatus()` hook buat auto-clear `lost_reason` begitu status diubah menjauh dari "Kalah". Ditemukan lewat browser-test agent.
3. **Livewire model-typed nullable public property tidak round-trip dengan aman.** Pola awal `public ?Lead $lead = null;` pada `LeadForm`/`CreateQuotation` gagal secara halus: saat `mount(?Lead $lead = null)` dipanggil tanpa argumen `lead` eksplisit (skenario "create", dan skenario test tanpa param), Laravel container meng-auto-instantiate `Lead` KOSONG (bukan `null`) untuk parameter typed tsb — sehingga `if ($lead)` selalu truthy walau modelnya belum ke-fetch dari DB (`$lead->exists === false`). Ini bikin field string ke-assign `null` dari model kosong dan gagal validasi/serialisasi Livewire dengan pesan generik "Invalid Livewire snapshot structure" yang sangat menyesatkan (root cause aslinya "Cannot assign null to property of type string", ketutup pesan lain).
   - **Fix pola:** ikuti konvensi `PackageBuilder::$packageId` yang sudah ada — simpan **id** (`public ?int $leadId`), bukan model Eloquent, sebagai public property. Model selalu di-fetch ulang di `save()`/`render()` dari id tsb.
   - **Guard tambahan:** cek `$lead?->exists` (bukan sekadar truthy) di `mount()` sebelum memperlakukan parameter sebagai lead yang sudah ada — defensif terhadap kasus container auto-instantiate di atas.
   - Berlaku untuk `LeadForm` (fixed) — `CreateQuotation::mount(Lead $lead)` non-nullable tidak kena bug ini karena selalu dipanggil dengan model asli dari blade (`:lead="$lead"`), tidak pernah lewat test tanpa param.

## Yang sudah jadi (teruji otomatis + browser)

- **4 migrasi** (`leads`, `lead_activities`, `quotations`, `quotation_items`) + 4 model (`Lead`, `LeadActivity`, `Quotation`, `QuotationItem`) di `app/Domain/Lead/`.
- **3 enum** (`LeadSource`, `LeadStatus`, `QuotationStatus`).
- **`QuotationGenerator`** — orkestrator snapshot, wrap `PackageCalculator` M5 tanpa modifikasi.
- **`LeadPolicy`/`QuotationPolicy`** — pola persis `PackagePolicy`.
- **3 Livewire component**: `LeadList` (papan + filter status/source), `LeadForm` (create/edit + activity log + validasi lost_reason), `CreateQuotation` (generate penawaran dari paket, embedded di halaman edit lead).
- **Public (no-auth)**: `LeadCaptureController` (form kontak + honeypot + throttle), `QuotationController` (halaman token publik + tombol WhatsApp).
- **Scheduled command** `quotations:expire` (`bootstrap/app.php withSchedule`, `everyMinute`).
- **Lang** `lead.php`/`quotation.php` × 3 locale (id/en/ar), termasuk template WhatsApp per bahasa.
- Sidebar admin: menu "Lead" baru, gated `@can('lead.manage')`.

## Hasil pengujian otomatis

- **123 test PASS** (117 lama + 6 baru: `LeadCrudTest` ×3, `LeadStatusTest` ×2, `CreateQuotationTest` ×1, `RateLockTest` ×1, `ExpiryTest` ×1, `PublicLinkTest` ×2 — total 10 test case baru dalam 6 file), 0 regresi.
- Pint bersih.
- **Dites di browser sungguhan** (Claude in Chrome, `php artisan serve`, login CS Bali): golden path submit lead manual PASS, validasi lost_reason PASS (nolak simpan tanpa alasan), papan lead + filter PASS, dropdown paket di "Buat Penawaran" kebukti berisi data paket M5 asli. Form publik `/contact` awalnya FAIL (bug #1 di atas), setelah fix diverifikasi ulang manual dan PASS — muncul pesan "Terima kasih, tim kami akan segera menghubungi Anda." dan lead baru tercatat.
- **Catatan minor belum diperbaiki (kosmetik, bukan blocker):** timestamp "X jam dari sekarang" di activity log kadang tampil future-looking (kemungkinan selisih timezone server vs browser saat render `diffForHumans()`) — di luar lingkup wajib PRD, dicatat buat modul lain yang nanti sentuh timezone display.

## Item "Sebaiknya" digarap belakangan (2026-09-18, sesi terpisah)

- **Pengingat follow-up**: kolom `follow_up_at` (nullable datetime) di `leads`, murni visual flag — bukan sistem notifikasi/email/queue (gak diminta eksplisit di PRD). Badge "Terlambat"/"Hari ini" di list & kanban (`Lead::isFollowUpDue()`), filter cepat "Perlu follow-up" di `LeadList`. Test: `LeadFollowUpTest.php`.
- **Tampilan kanban**: toggle List/Kanban di `LeadList` (pola persis `PackageList` — Alpine+localStorage, zero round-trip). Kolom per `LeadStatus`, card klik → edit lead. **Tanpa drag-and-drop** — ganti status tetap lewat form (PRD cuma minta "tampilan kanban", bukan workflow drag-drop).
- Dites di browser sungguhan (Super Admin, branch Bali): kanban board render 6 kolom sesuai status + card kebukti klik-able, badge "Terlambat" muncul benar di kanban & list, filter "Perlu follow-up" ke-verifikasi mengurangi hasil jadi cuma lead overdue non-won/lost. **Catatan jujur**: pengisian field `datetime-local` via automation browser tool gagal berulang kali (keterbatasan tool, bukan bug app — `type`/`key` action gak bisa drive widget datetime-local composite Chrome di environment ini) — path simpan-nya sendiri sudah dibuktikan benar lewat automated test (`LeadFollowUpTest::test_follow_up_date_is_saved_through_the_form`, exercise `LeadForm::save()` yang sama persis), dan sisi TAMPILAN badge dibuktikan di browser sungguhan pakai data yang di-set langsung lewat `php artisan tinker` (bukan lewat form) untuk isolasi masalah tool vs kode.

## Di luar lingkup (sengaja belum digarap)

- Multi-branch selection di form lead publik — endpoint publik otomatis assign ke branch aktif pertama (`Branch::where('is_active', true)->first()`); pemilihan cabang oleh tamu publik belum ada UI (storefront penuh baru M10).
- Integrasi quotation → booking (konversi penawaran disetujui jadi pemesanan) — itu scope M7, quotation di M6 murni berhenti di tautan publik + tombol WhatsApp.
- PDF export untuk quotation — PRD M6 tidak minta PDF (beda dari M5 itinerary), cukup halaman web publik.
