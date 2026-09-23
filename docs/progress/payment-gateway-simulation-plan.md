# Rencana Simulasi Payment Gateway

Status: **backend + UI simulasi dikerjakan 2026-09-23** · Dibuat: 2026-09-23

Default yang dipakai (owner belum memutuskan, bisa diubah): DP = `PAYMENT_DP_PERCENT` (30%) atau lunas; biaya channel ditanggung agensi (dicatat di `channel_fee_minor`, tidak ditambahkan ke tagihan); transfer manual tetap jalan berdampingan. Aktifkan dengan `PAYMENT_PROVIDER=simulator` di `.env`. Webhook `POST /webhooks/payments/{provider}` dengan header `X-Signature` = HMAC-SHA256(body, `PAYMENT_WEBHOOK_SECRET`).

## 1. Tujuan

Untuk presentasi, tunjukkan alur bayar online dari ujung ke ujung: customer klik "Bayar", memilih metode, pembayaran sukses, lalu status booking dan antrian CS/Finance berubah sendiri. Semua ini tanpa gateway sungguhan.

Syarat utama: **simulator ini adalah kerangka gateway yang asli, bukan mockup sekali pakai.** Waktu gateway sungguhan dipilih (Midtrans / Xendit / DOKU / Stripe, dan lain-lain), yang ditambah cukup satu kelas provider plus konfigurasi. Alur, tabel, webhook, dan UI tetap.

## 2. Yang sudah ada (bisa dipakai ulang)

- `App\Domain\Finance\Contracts\PaymentProviderInterface`: `createIntent`, `verifyPayment`, `processRefund`.
- `ManualTransferProvider`: implementasi sekarang (transfer manual, dicek admin).
- Tabel `payment_intents`: channel, amount, currency, fx_rate, channel_fee, status `pending/completed/cancelled/expired`, `expires_at`.
- `PaymentService`: guard status, row lock, anti self-approval, refund. Transisi status booking lewat `BookingStateMachine`.
- `PaymentChannel` enum (`bank_transfer`, `international_card`) dan biaya channel (`payment_channel_costs`).
- Link quotation publik `/q/{token}`: titik masuk customer.
- Storefront checkout (`App\Models\Booking`) dengan upload bukti dan verifikasi admin di halaman Bookings.

## 3. Alur yang disimulasikan

```
Customer (link quotation / booking)         Indogate                          "Gateway" (simulator)
  1. Klik "Bayar Sekarang"  ───────────▶  createIntent() → payment_intents
                                           (pending, token, checkout_url)
  2. Redirect ke checkout_url ─────────────────────────────────────────────▶  Halaman checkout simulasi
                                                                              pilih: VA BCA/Mandiri, QRIS, Kartu
  3. Klik "Bayar" / "Gagalkan" / "Biarkan kedaluwarsa"
                                           ◀──────── webhook bertanda tangan (HMAC) ─────────
  4. WebhookController: verifikasi signature, idempotensi (event_id unik),
     intent → completed; buat Payment (status verified, verified_by = sistem),
     BookingStateMachine: confirmed → partially_paid / paid
  5. Customer diarahkan ke halaman "Pembayaran berhasil" / "gagal"
  6. Admin: Meja CS (antrian tagih berkurang), Keuangan > Pembayaran
     menampilkan badge "Gateway (Simulasi)", Dashboard pendapatan naik
```

Skenario yang bisa dipilih di presentasi:
- **Sukses penuh.** Booking jadi `paid`.
- **DP 30%.** Booking jadi `partially_paid`, dan sisa tagihan tampil di Meja CS.
- **Gagal / ditolak bank.** Intent `cancelled`, booking tidak berubah, customer bisa coba lagi.
- **Kedaluwarsa.** Scheduler menandai intent `expired` setelah `expires_at`.
- **Webhook ganda.** Kirim ulang event yang sama; tidak ada pembayaran ganda. Ini poin jualan tentang keandalan.
- **Refund** dari admin lewat `processRefund`. Simulator mencatat refund sebagai berhasil.

## 4. Desain teknis

### 4.1 Provider & konfigurasi
- `config/payments.php`: `default` = `env('PAYMENT_PROVIDER', 'manual')`, dengan daftar provider `manual`, `simulator`, dan nanti `midtrans` / `xendit` / ...
- Binding `PaymentProviderInterface` di `AppServiceProvider` membaca config itu.
- `SimulatedGatewayProvider implements PaymentProviderInterface`:
  - `createIntent`: membuat intent `pending` + `public_token` + `checkout_url` ke halaman simulator, `expires_at` +24 jam.
  - `verifyPayment`: dipanggil dari webhook, bukan oleh manusia.
  - `processRefund`: langsung `completed` dengan `provider_reference` palsu.
- **Pengaman:** simulator menolak jalan kalau `app()->isProduction()` (exception saat boot). Tidak mungkin ada uang "palsu" di produksi.

### 4.2 Perubahan skema (satu migration, aditif)
- `payment_intents`: `provider` (string), `public_token` (unik), `provider_reference` (nullable, id transaksi gateway), `checkout_url`, `paid_at`, `failure_reason`.
- `payments`: `source` (`manual` | `gateway`), `provider_reference` (unik nullable), `payment_intent_id` (nullable FK, kalau belum ada).
- Tabel baru `payment_webhook_events`: `provider`, `event_id` (unik per provider), `payload` (json), `signature_valid`, `processed_at`. Dipakai untuk idempotensi dan audit.

### 4.3 Route
- `GET /{locale}/pay/{intent:public_token}`: halaman checkout simulasi. Publik, throttled, dan hanya terdaftar kalau provider = simulator.
- `POST /{locale}/pay/{intent:public_token}/simulate`: tombol Sukses/Gagal/Expired, yang lalu memanggil webhook internal.
- `POST /webhooks/payments/{provider}`: tanpa locale dan tanpa CSRF (dikecualikan), signature HMAC `PAYMENT_WEBHOOK_SECRET`. **Endpoint ini juga yang nanti dipakai gateway asli.**
- `GET /{locale}/pay/{intent:public_token}/return`: halaman hasil untuk customer.

### 4.4 Pemrosesan webhook (bagian yang dipertahankan untuk produksi)
1. Validasi signature. Kalau gagal: 401 dan dicatat.
2. `payment_webhook_events` insert dengan `event_id` unik. Kalau sudah ada: 200 tanpa proses ulang.
3. Transaksi + `lockForUpdate` pada intent. Status harus `pending`.
4. Sukses: `PaymentService::recordPayment(..., source: gateway)`, lalu verifikasi otomatis oleh **akun sistem** (`system@indogate.local`, tanpa login). Aturan anti self-approval tetap berlaku untuk jalur manual.
5. `BookingStateMachine` memutuskan `partially_paid` / `paid` berdasarkan `remainingBalanceMinor()`.
6. Activity log `payments` dan notifikasi (email konfirmasi yang sudah ada).
7. **Bayar terlambat** (event `paid` untuk intent yang sudah `expired`/`cancelled`): kalau booking masih punya tagihan, pembayaran tetap dicatat (`processed_late`). Kalau booking sudah lunas/batal, tidak dicatat, hanya activity log "needs manual refund review" untuk Finance (`needs_review`).

### 4.5 UI
- **Halaman quotation publik & booking customer:** tombol "Bayar Sekarang" (penuh / DP) hanya tampil kalau provider ≠ manual. Area storefront milik rekan tim: yang ditambah hanya logic/komponen tombol, gaya disepakati dengan rekan tim.
- **Halaman checkout simulasi:** netral, label **"MODE SIMULASI — tidak ada uang yang ditarik"** selalu terlihat. Isinya ringkasan booking, pilihan metode (VA/QRIS/Kartu dengan nomor dummy), dan tombol skenario.
- **Admin:**
  - Booking detail: timeline intent + pembayaran, badge sumber (Manual/Gateway).
  - Keuangan > Pembayaran: filter sumber.
  - Meja CS: tombol "Kirim link bayar" yang menyalin link atau membuka WhatsApp dengan link pembayaran.

## 5. Tahapan kerja

| # | Pekerjaan | Estimasi |
|---|-----------|----------|
| 1 | Config provider + binding + migration aditif | 0,5 hari |
| 2 | `SimulatedGatewayProvider` + pengaman produksi | 0,5 hari |
| 3 | Webhook controller (signature, idempotensi, lock, state machine) + test | 1 hari |
| 4 | Halaman checkout simulasi + halaman hasil (id/en/ar, RTL) | 1 hari |
| 5 | Tombol bayar di quotation publik/booking + "Kirim link bayar" di Meja CS | 0,5 hari |
| 6 | Tampilan admin (timeline, badge, filter) | 0,5 hari |
| 7 | Scheduler expire intent + skenario refund | 0,25 hari |
| 8 | Seeder skenario demo + QA Chrome + naskah demo | 0,5 hari |

Total ± **4,5 hari kerja**.

## 6. Test otomatis (minimal)

- Webhook signature salah ditolak dengan 401.
- Event sama dikirim dua kali menghasilkan tepat 1 `Payment`.
- Bayar penuh membuat booking `paid`; DP membuat `partially_paid` dengan sisa benar (termasuk kurs terkunci non-IDR).
- Intent expired/cancelled tidak bisa dibayar.
- Simulator tidak bisa aktif di environment `production`.
- Route checkout simulasi 404 kalau provider = manual.

## 7. Naskah demo presentasi (± 5 menit)

1. CS membuat quotation dari lead, lalu klik "Kirim link bayar" (terbuka WhatsApp dengan link).
2. Buka link sebagai customer. Pilih DP 30%, metode VA BCA, lalu klik **Sukses**.
3. Halaman "Pembayaran berhasil". Di admin: Meja CS menampilkan sisa tagihan, booking `DP Terbayar`, Keuangan mencatat pembayaran dengan badge Gateway.
4. Bayar pelunasan dengan QRIS. Booking jadi `Lunas` dan hilang dari antrian tagih.
5. (Opsional) Tunjukkan webhook ganda tidak menggandakan pembayaran, dan transfer manual tetap bisa dipakai berdampingan.

## 8. Jalan menuju gateway asli

Saat vendor dipilih:
1. Tambah `MidtransProvider` (atau lainnya) dengan `createIntent` memanggil API Snap/Invoice dan menyimpan `checkout_url` + `provider_reference`.
2. Adapter webhook: memetakan payload vendor ke event internal (`paid` / `failed` / `expired` / `refunded`) dan memverifikasi signature vendor.
3. Isi `.env`: kunci API, secret webhook, `PAYMENT_PROVIDER=midtrans`.
4. Rekonsiliasi harian: bandingkan laporan settlement vendor dengan `payments`.

Tidak ada perubahan di tabel, state machine, UI admin, atau Meja CS.

## 9. Keputusan yang dibutuhkan dari owner

1. Kandidat vendor. Untuk customer GCC yang dominan membayar dengan kartu internasional, perlu gateway yang mendukung kartu asing + multi-currency (SAR/USD). VA/QRIS relevan untuk customer domestik.
2. Opsi DP: persentase tetap (misalnya 30%) atau bebas diisi?
3. Siapa yang menanggung biaya channel (MDR): customer (ditambahkan ke tagihan) atau agensi? Data `payment_channel_costs` sudah ada.
4. Transfer manual tetap ada berdampingan dengan gateway? Rekomendasi: **ya**, untuk grup corporate.
