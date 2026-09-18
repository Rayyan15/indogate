# M7 — Booking & Order

**Status:** SELESAI · **Tanggal:** 2026-09-19 · **Referensi:** `docs/PRD_Indogate_v3.1.md` §M7

## Keputusan arsitektur (final, dikonfirmasi owner)

1. **Tabel `bookings` lama (MVP) TIDAK disentuh.** Sudah ada dari awal project (customer_id, driver_id, booking_number uuid, total_amount), dipakai `BookingController`/`PaymentController`/customer dashboard — gak nyambung ke quotation/package sama sekali. M7 dibangun 100% terpisah: `App\Domain\Booking\*`, tabel `package_bookings`. Pola persis M3 (Catalog lama vs baru) dan M4 — dikonfirmasi eksplisit ke owner sebelum mulai (bukan asumsi sepihak).
2. **State machine wajib**, satu-satunya jalan ubah status booking (`BookingStateMachine`). Transisi tak terdaftar melempar `InvalidBookingTransitionException`, bukan diabaikan. Cancel tanpa alasan melempar `InvalidArgumentException`. Setiap transisi sukses = 1 baris `booking_status_histories` + 1 activity log, dalam satu `DB::transaction()`.
3. **Konversi quotation→booking langsung mulai status `confirmed`**, bukan `draft`. `Quotation.status` (M6) sudah merepresentasikan siklus draft/quoted-nya sendiri; booking baru cuma butuh 1 baris history awal (`draft→confirmed`, alasan "Dikonversi dari penawaran") biar "setiap perpindahan ada riwayat" tetap terpenuhi tanpa replay paksa draft→quoted→confirmed yang gak relevan lagi.
4. **Paspor terenkripsi**: cast `'passport_number' => 'encrypted'` di `BookingGuest`, reuse pola existing `App\Models\Customer` (satu-satunya precedent enkripsi PII di codebase ini sebelum M7).
5. **Dokumen paspor**: disk privat `local`, akses cuma lewat signed route (`URL::temporarySignedRoute`) + `throttle:20,1`, tetap di dalam grup `auth`+`permission:booking.manage` (beda dari M6 quotation token yang publik — ini staff internal). Setiap akses dicatat manual `activity()->causedBy()->performedOn($guest)->log(...)` — codebase belum pernah log *read* access sebelumnya (cuma state-change), jadi ini pola baru, bukan copy dari existing.
6. **"Blokir unduh massal" = gak ada endpoint zip/bulk sama sekali** + signed URL per-file + throttle. Gak bikin sistem deteksi anomali — di luar scope PRD yang cuma minta "diblokir".
7. **Voucher/itinerary via queue**, copy struktur `GeneratePackageItineraryPdf` (M5) persis: `GenerateBookingVoucher` job, `Cache::put` readiness key, Livewire `wire:poll` (gak ada infra broadcast).
8. **Transisi otomatis berbasis tanggal**: command `bookings:transition-status`, loop per-baris lewat `BookingStateMachine` (BUKAN bulk `->update()`, karena tiap baris wajib history+log). Jadwal `->hourly()` (granularitas tanggal, beda dari `ExpireQuotations` yang per-menit).
9. **Kalender keberangkatan**: grid bulan native (tabel HTML, `x-ui.*`), tanpa library JS eksternal.
10. **Halaman status untuk tamu (Sebaiknya) — belum digarap**, sama pola M6 (reminder/kanban ditunda sampai diminta eksplisit).

## Struktur data baru

`App\Domain\Booking\Models\`: `PackageBooking`, `BookingGuest`, `BookingNote`, `BookingStatusHistory` — 4 migrasi baru (`package_bookings`, `booking_guests`, `booking_notes`, `booking_status_histories`), semua terpisah total dari tabel `bookings`/`booking_items`/`payments` lama.

`App\Enums\BookingStatus`: draft, quoted, expired, confirmed, partially_paid, paid, in_progress, completed, cancelled (persis tabel state machine PRD, `expired` ditambah karena disebut di tabel transisi walau tidak ada di daftar kolom literal).

## Domain classes

- **`BookingStateMachine`** — const `TRANSITIONS` persis tabel PRD. Method `transition()` (validasi+eksekusi) dan `nextStatuses()` (buat render tombol UI).
- **`ConvertQuotationToBooking`** — generate kode unik `BK-XXXXXXXX` (retry-loop anti-collision), copy `total_minor` dari sum `quotation_items`, insert booking `confirmed` + 1 baris history + activity log, semua dalam transaksi.

## Dokumen paspor & voucher

- `BookingGuestDocumentController@download` — cek exists, authorize, log akses, stream dari disk privat. Route: `admin.package-bookings.guests.passport-download`, middleware `signed`+`throttle:20,1`.
- `GenerateBookingVoucher` job — render `pdf.booking-voucher` (data tamu + itinerary dari `quotation->package->items`), simpan `booking-exports/{id}/...`, readiness via cache key + `wire:poll`.

## Livewire & UI

- **`PackageBookingList`** — list + toggle List/Kalender (pola Alpine+localStorage persis `LeadList`/`PackageList`). Kalender = grid bulan 7 kolom.
- **`PackageBookingShow`** — gabungan info booking + tombol transisi status + form batalkan (wajib alasan) + manajemen tamu (tambah/hapus, upload paspor) + catatan + export voucher.
- Tombol **"Jadikan Booking"** ditambahkan ke `create-quotation.blade.php` (M6) — form kecil tanggal berangkat/pulang, panggil `ConvertQuotationToBooking`, redirect ke halaman booking. Kalau quotation sudah pernah dikonversi, tombol berubah jadi "Lihat Booking" (dicek lewat `bookedQuotationIds` map, bukan izinin duplikat booking dari 1 quotation).
- Sidebar admin: menu "Pemesanan" baru, gated `@can('booking.manage')`.

## Permission

**Tidak ada permission baru** — reuse `booking.manage` yang sudah di-seed sejak M1, persis seperti disebutkan di komentar `RolesAndPermissionsSeeder`.

## Hasil pengujian otomatis

- **147 test PASS** (140 lama + 7 file baru M7: `BookingStateMachineTest` unit ×15 kasus [ditulis & dijalankan DULUAN sebelum lanjut fitur lain, sesuai PRD step 3], `ConvertQuotationTest` ×2, `GuestDocumentTest` ×1, `DocumentAccessTest` ×1, `CancellationTest` ×2, `StatusHistoryTest` ×1), 0 regresi.
- Pint bersih.
- `GuestDocumentTest` membuktikan nilai RAW di database bukan plaintext (bukan cuma cek accessor ke-decrypt).
- `DocumentAccessTest` membuktikan akses tanpa signed URL ditolak (403) DAN akses valid tercatat di `activity_log` (assert langsung ke tabel Spatie Activitylog).

<!-- Browser verification section filled in after the background QA agent reports back. -->

## Di luar lingkup (sengaja belum digarap)

- Halaman status untuk tamu (PRD prioritas "Sebaiknya").
- Drag-and-drop di kalender keberangkatan — klik card buka detail booking, ubah tanggal tetap lewat halaman lain (di luar scope PRD M7 yang cuma minta "kalender keberangkatan", bukan reschedule via drag).
- Rekonsiliasi dengan tabel `bookings`/`Payment`/`PaymentProof` lama (customer/driver flow MVP) — dibiarkan utuh, sesuai keputusan owner. Kapan digabung/dimigrasi jadi keputusan modul lain nanti.
