# Rencana Notifikasi (In-App + Push) — Panel Admin

Status: **Fase 1 (lonceng in-app) selesai 2026-09-24; Fase 2 (web push + preferensi + jam tenang) selesai 2026-09-24** · Dibuat: 2026-09-23

## 0. Status pengerjaan (2026-09-24)

Selesai: seluruh pemicu §2 (termasuk H-1 tanpa driver lewat permission `driver.assign`, quotation, follow-up), lonceng + riwayat + prune, dan Fase 2:
- Paket `laravel-notification-channels/webpush` (VAPID di `.env`: `VAPID_SUBJECT/PUBLIC_KEY/PRIVATE_KEY`, dibuat dengan `php artisan webpush:vapid`; di Windows perlu `OPENSSL_CONF` ke `extras/ssl/openssl.cnf`). Tabel `push_subscriptions`, `users.notification_preferences`.
- `public/sw.js`, `public/manifest.json`, `public/icons/icon.svg`, `public/js/staff-push.js` (izin diminta hanya setelah tombol ditekan).
- Endpoint `POST/DELETE admin/push-subscriptions` (staff saja, hanya milik sendiri). Subscription mati (404/410) dihapus otomatis oleh `ReportHandler` paket saat pengiriman.
- Halaman Notifikasi: panel aktifkan perangkat + preferensi push per jenis + jam tenang (default 22:00-07:00 waktu cabang). `StaffNotification::via()` menambah channel push hanya bila jenis aktif dan tidak sedang jam tenang; in-app selalu masuk.

Sengaja belum dikerjakan:
- Pengecualian jam tenang untuk pembayaran gagal booking berangkat <= 24 jam: menunggu keputusan owner (§9 no. 2).
- Penggabungan "3 lead baru" dalam 1 menit: dedupe per record sudah ada; penggabungan butuh keputusan format teks, ditunda.
- Isi push memakai locale default aplikasi (user belum punya kolom locale).
- Menu topbar khusus: pintu masuk lewat "Lihat semua" di dropdown lonceng ke halaman Notifikasi.
- QA perangkat nyata (Chrome Android, iOS PWA) dan naskah demo: perlu HTTPS/localhost dan perangkat.

## 1. Tujuan

Staf tidak perlu terus me-refresh Meja CS atau halaman Keuangan. Kejadian penting langsung sampai ke orang yang tepat:
- **Fase 1:** lonceng notifikasi di panel admin.
- **Fase 2:** push ke browser atau HP staf, tetap sampai walaupun tab sudah ditutup.

Setiap notifikasi membawa link langsung ke record terkait. Cakupannya panel admin saja; storefront tidak disentuh.

## 2. Siapa menerima apa

| Kejadian | Penerima | Sumber kejadian di kode |
|---|---|---|
| Lead baru dari website | Semua CS cabang tsb (+ Admin Cabang) | `Public\LeadCaptureController::store` |
| Pembayaran online **berhasil** | CS yang membuat booking + Finance cabang | `PaymentService::recordGatewayPayment` |
| Pembayaran online **gagal / kedaluwarsa** | CS yang membuat booking | `GatewayCheckout::handleWebhook` (event failed/expired), `payments:expire-intents` |
| Pembayaran manual dicatat, menunggu verifikasi | Finance cabang | `PaymentService::recordPayment` |
| Bukti transfer order online diupload | Finance cabang | upload bukti di checkout storefront (status `payment_submitted`) |
| Quotation kedaluwarsa ≤ 48 jam | CS penanggung jawab lead (`leads.assigned_to`), fallback semua CS cabang | job terjadwal harian |
| Booking berangkat besok, driver belum di-assign | Admin Cabang (+ Super Admin) | job terjadwal harian 16:00 waktu cabang |
| Follow-up lead jatuh tempo | CS penanggung jawab | job terjadwal (pakai `Lead::isFollowUpDue`) |

Aturan penerima:
- **Scope cabang:** hanya user aktif (`is_active`) di `branch_id` yang sama dengan record.
- **Penerima ditentukan lewat permission, bukan nama role.** Contoh: "Finance" = user dengan `payment.verify`, "CS" = user dengan `lead.manage`. Dengan begitu role custom dari halaman Hak Akses Role ikut otomatis.
- **Super Admin tidak menerima semua notifikasi.** Dia cuma dapat notifikasi yang juga dikirim ke perannya, supaya tidak kebanjiran.
- **Tidak ada notifikasi untuk pelaku sendiri.** Kalau CS sendiri yang mencatat pembayaran, CS itu tidak diberi tahu.

## 3. Yang sudah ada (audit)

- **Mail:** `App\Mail\BookingConfirmed` dikirim lewat queue (`Mail::...->queue`). `MAIL_MAILER=log` di lokal. Folder `app/Notifications` **belum ada**, dan tabel `notifications` **belum ada**.
- **Queue:** `QUEUE_CONNECTION=database`, tabel `jobs` sudah ada. Di server perlu `php artisan queue:work` jalan terus (supervisor), dan `schedule:run` via cron sudah dibutuhkan untuk job yang ada.
- **Broadcasting:** `BROADCAST_CONNECTION=log`. Belum ada Reverb/Pusher dan belum ada `config/broadcasting.php`, jadi belum ada websocket.
- **Livewire polling** sudah dipakai (`wire:poll.3s` di booking detail dan package builder), jadi polacnya sudah familiar di proyek ini.
- **PWA / service worker:** `public/` belum punya `manifest.json` atau `sw.js`.
- **Activity log** (spatie) sudah mencatat kejadian finance/lead. Ini jejak audit, bukan saluran notifikasi, dan tetap dipisah.
- **Titik kejadian sudah terpusat:** `PaymentService` (semua perubahan pembayaran), `GatewayCheckout::handleWebhook`, `BookingStateMachine`, `LeadCaptureController`. Pemicu notifikasi cukup ditaruh di titik-titik ini.

## 4. Desain

### 4.1 Fase 1 — Lonceng in-app (tanpa infrastruktur baru)
- `php artisan notifications:table` untuk migration tabel `notifications` bawaan Laravel.
- Satu kelas Notification per kejadian di `app/Notifications/` (misalnya `OnlinePaymentReceived`, `NewWebsiteLead`, ...), dengan `via()` = `['database']` (+ `webpush` di fase 2). Semuanya `ShouldQueue`, supaya webhook dan request customer tidak jadi lambat.
- Payload `toArray`: `type`, `title_key`, `params`, `url` (route admin ke record), `branch_id`, `severity` (info/success/warning).
- Teks dirender saat tampil pakai `__()` dan locale staf, bukan disimpan sebagai teks jadi. Jadi ganti bahasa id/en/ar tetap benar.
- Service kecil `App\Support\Notify` (satu tempat) untuk:
  - memilih penerima (permission + cabang + aktif, tanpa pelaku);
  - dedupe: kunci `type + record_id` yang sama dalam 10 menit tidak dikirim ulang (cache lock);
  - memanggil `Notification::send`.
- Livewire `admin.notification-bell` di top bar admin (sebelah pemilih bahasa):
  - badge jumlah belum dibaca dan dropdown 10 terbaru;
  - klik membuka `url` sekaligus menandai dibaca; ada tombol "Tandai semua dibaca";
  - `wire:poll.30s` (hemat; cukup "hampir realtime" tanpa websocket), berhenti polling saat tab tidak terlihat (`wire:poll.visible`).
- Halaman `admin/notifications` untuk riwayat lengkap dan filter per jenis.
- Pembersihan: notifikasi yang sudah dibaca dan berumur lebih dari 90 hari dihapus oleh job terjadwal.

### 4.2 Fase 2 — Web Push (browser & HP staf)
- Paket `laravel-notification-channels/webpush` (VAPID, tanpa layanan pihak ketiga berbayar), plus tabel `push_subscriptions`.
- `public/manifest.json` + `public/sw.js`:
  - service worker menerima push lalu menampilkan notifikasi OS; klik membuka `url`;
  - manifest dengan ikon INDOGATE supaya panel admin bisa "Install" ke layar utama HP.
- Tombol "Aktifkan notifikasi di perangkat ini" di halaman profil/notifikasi. Izin browser **hanya diminta setelah staf menekan tombol**, tidak otomatis saat login.
- Satu user bisa punya beberapa perangkat. Subscription yang ditolak browser (410) dihapus otomatis.
- **iOS:** Web Push hanya jalan di Safari iOS 16.4+ **dan** kalau panel sudah di-"Add to Home Screen" (PWA). Ini perlu masuk panduan staf. Android Chrome dan desktop langsung jalan.
- **Isi push dibuat minimal**, misalnya "Pembayaran BK-XXXX diterima". Tidak ada data sensitif seperti nomor paspor atau nominal detail, karena notifikasi tampil di lock screen.

### 4.3 Preferensi & kenyamanan
- Kolom `users.notification_preferences` (json): on/off per jenis × saluran (in-app selalu on; push bisa dimatikan per jenis).
- **Jam tenang** per user (default 22:00–07:00 waktu cabang): push dibungkam (tidak ditunda atau dikirim ulang), in-app tetap masuk. Pengecualian yang diusulkan: "pembayaran gagal" untuk booking yang berangkat ≤ 24 jam.
- **Throttle:** maksimal 1 push per jenis per record per 10 menit (sama dengan dedupe). Lead website yang masuk beruntun digabung jadi "3 lead baru" kalau datang dalam 1 menit.

### 4.4 Fase 3 (opsional, nanti) — Customer
WhatsApp (WA Business API / penyedia lokal) atau email ke customer untuk "pembayaran diterima", "H-1 keberangkatan", dan sejenisnya. Ini di luar panel admin dan butuh keputusan vendor. Kelas Notification yang sama tinggal menambah channel.

## 5. Tahapan kerja

| # | Pekerjaan | Estimasi |
|---|-----------|----------|
| 1 | Tabel `notifications`, service `Notify` (penerima, dedupe), 6 kelas Notification + lang id/en/ar | 1 hari |
| 2 | Pasang pemicu di `PaymentService`, `GatewayCheckout`, `LeadCaptureController`, upload bukti storefront (logic saja) | 0,5 hari |
| 3 | Job terjadwal: quotation ≤ 48 jam, H-1 tanpa driver, follow-up jatuh tempo, pembersihan 90 hari | 0,5 hari |
| 4 | Lonceng Livewire + halaman riwayat + "tandai dibaca" | 1 hari |
| 5 | **Fase 2:** webpush + VAPID + `sw.js` + manifest + tombol aktifkan perangkat | 1,5 hari |
| 6 | Preferensi per user + jam tenang | 0,5 hari |
| 7 | QA Chrome (desktop + Android), panduan iOS, naskah demo | 0,5 hari |

Fase 1 (langkah 1–4): ± **3 hari**. Fase 2 (5–7): ± **2,5 hari**.

## 6. Test otomatis (minimal)

- Pembayaran gateway berhasil memberi notifikasi ke CS pembuat booking + Finance **cabang yang sama**. User cabang lain dan user nonaktif tidak dapat.
- Pelaku tidak menerima notifikasi atas aksinya sendiri.
- Webhook ganda tetap menghasilkan 1 notifikasi (dedupe).
- Lead website memberi notifikasi ke user ber-`lead.manage` di cabang itu, termasuk role custom.
- Job H-1 hanya memilih booking berangkat besok yang belum punya driver aktif.
- Lonceng: jumlah belum dibaca benar, klik menandai dibaca dan redirect ke `url`, dan user tidak bisa membaca notifikasi milik orang lain.
- Jam tenang: push dibungkam (tidak ditunda), notifikasi database tetap tersimpan.
- `Notification::fake()` untuk semua test di atas. Push tidak dikirim sungguhan.

## 7. Naskah demo presentasi (± 3 menit)

1. Staf CS membuka panel admin di HP Android, lalu menekan "Aktifkan notifikasi di perangkat ini" dan mengizinkan.
2. Di laptop, buka link bayar booking sebagai customer, lalu bayar DP di halaman **simulasi** gateway dan klik "Bayar (sukses)".
3. Beberapa detik kemudian HP CS berbunyi: "Pembayaran DP BK-XXXX diterima". Tap langsung membuka detail booking.
4. Di laptop Finance, lonceng menampilkan badge 1 dengan isi yang sama.
5. (Opsional) Kirim lead dari form website, lalu semua CS cabang menerima "Lead baru: {nama}".

Syarat demo: `queue:work` harus jalan (atau `QUEUE_CONNECTION=sync` khusus demo), dan halaman diakses lewat HTTPS atau `localhost`, karena Web Push butuh secure context.

## 8. Risiko

- **Queue worker mati** berarti notifikasi tidak terkirim tanpa ada yang sadar. Mitigasi: supervisor + monitor `failed_jobs`, dan cek ringan di dashboard Super Admin ("antrian tertunda > 10 menit").
- **HTTPS wajib untuk push.** Produksi harus HTTPS (sudah seharusnya). Untuk demo di jaringan lokal, pakai `localhost` atau tunnel HTTPS.
- **iOS:** staf iPhone harus install PWA dulu. Tanpa itu mereka hanya dapat lonceng in-app.
- **Kebisingan:** terlalu banyak notifikasi membuat staf mengabaikannya. Karena itu ada dedupe, penggabungan, jam tenang, dan penerima berbasis cabang + permission.
- **Kunci VAPID** harus disimpan di `.env` dan jangan diganti setelah produksi. Kalau diganti, semua subscription lama tidak berlaku.
- **Polling 30 detik** = 1 request ringan per tab aktif per 30 detik. Aman untuk jumlah staf sekarang. Kalau staf bertambah banyak, ganti ke Reverb (websocket) tanpa mengubah kelas Notification.

## 9. Keputusan yang dibutuhkan dari owner

1. **Fase 2 (push ke HP) langsung dikerjakan**, atau cukup lonceng in-app dulu? Rekomendasi: lonceng dulu, push setelah domain produksi HTTPS siap.
2. **Jam tenang:** default 22:00–07:00 untuk semua staf, dan pengecualian mana yang tetap boleh bunyi?
3. **Super Admin (Mr. Ahmed)** perlu menerima notifikasi apa saja? Usulan: hanya pembayaran di atas nominal tertentu + H-1 tanpa driver, tidak semuanya.
