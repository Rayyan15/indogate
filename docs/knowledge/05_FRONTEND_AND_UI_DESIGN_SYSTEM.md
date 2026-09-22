# 05. Frontend dan Sistem Desain UI/UX — Indogate

Dokumen ini menguraikan sistem desain resmi **"Indogate Editorial System"** yang mengatur antarmuka panel admin, portal pelanggan, dan storefront publik.

---

## 1. Filosofi Desain: Indogate Editorial System vs "AI Slop"

Sistem desain Indogate menolak tegas estetika generik berbasis template instan (seperti glassmorphism ungu/slate, kartu tebal `rounded-2xl`, tombol gradien emas palsu `#D4AF37`, dan bayangan drop-shadow tebal).

Sebagai gantinya, Indogate mengusung gaya **Majalah Perjalanan Mewah & Buku Besar Perbankan** (*High-end Travel Magazine Meets Bank Ledger*):
1. **Permukaan Bersih Berlatar Hangat:** Latar kanvas utama menggunakan `bg-neutral-50` (`#F8F8F7`) dengan kartu flat `bg-neutral-0` (`#FFFFFF`).
2. **Garis Pembatas Rambut (*Hairline 1px*):** Struktur antarmuka dibentuk oleh garis tipis 1px `border-neutral-200` (`#E2E2E0`). Bayangan drop-shadow dilarang pada permukaan datar dan hanya diizinkan untuk elemen mengambang nyata (`shadow-sm` pada sticky folio dan dropdown).
3. **Kontras Tipografi Ekstrem:** Memadukan judul serif ramping yang megah (*Fraunces* serif `font-display`) dengan label mikro kapital renggang 10px (*Eyebrow* `tracking-[0.18em]`) dan angka tabular monospace (*JetBrains Mono* `tabular-nums`).

---

## 2. Palet Warna Resmi dan Proporsi Layar

```
+-------------------------------------------------------------------------------+
|  85% Netral Hangat   : #F8F8F7 (kanvas), #FFFFFF (panel), #E2E2E0 (garis)     |
|  10% Merah Brand     : #C41230 (red-600) — Tepat 1 tombol utama per layar     |
|   3% Biru Brand      : #1F5896 (blue-600) — Khusus tautan teks dan info badge |
|   2% Semantik        : Sukses (#1E7D5A), Peringatan (#B45309), Bahaya (#C41230)|
+-------------------------------------------------------------------------------+
```

> [!WARNING]
> **Aturan Ketat Tombol:**
> Dalam satu layar tampilan, **HANYA BOLEH ADA TEPAT SATU TOMBOL MERAH** (`variant="primary"`). Aksi lain wajib menggunakan varian `secondary`, `ghost`, atau `danger`. Warna biru dilarang keras dijadikan tombol aksi.

---

## 3. Tata Letak Asimetris: Grid 8/4

Halaman kerja operasional (seperti Package Builder, Quotation, Booking Show, dan Finance) mengadopsi tata letak asimetris **Grid 8/4**:
- **Kolom Kiri (8 Kolom):** Ruang kerja utama pengguna yang menampung formulir input, tabel rincian transaksi, atau segmen itinerari perjalanan harian.
- **Kolom Kanan (4 Kolom):** Menampung ringkasan finansial (*docket / folio*) yang menempel secara *sticky* (`top-24`). Total harga, status dokumen, dan tombol aksi utama selalu terlihat saat pengguna menggulir halaman panjang.

---

## 4. Pustaka Komponen Blade UI (`resources/views/components/ui/`)

Terdapat 19 komponen resmi `<x-ui.*>` yang dapat dilihat panduan visualnya pada rute `/admin/styleguide`:

| Komponen | Tag Blade | Fungsi & Karakteristik |
|---|---|---|
| **Page Header** | `<x-ui.page-header>` | Header halaman terpadu berisi eyebrow modul, judul besar serif, deskripsi singkat (*lede*), dan slot aksi kanan. |
| **Eyebrow** | `<x-ui.eyebrow>` | Teks metadata kapital mikro 10px dengan spasi renggang (`tracking-[0.18em] text-neutral-400`). |
| **Stat** | `<x-ui.stat>` | Indikator KPI bergaya buku besar bank dengan garis pembatas hairline di sisi awal (`border-s`), angka tabular besar, dan label kapital. |
| **Panel** | `<x-ui.panel>` | Permukaan kartu datar bersih (`bg-neutral-0 border border-neutral-200 rounded`) pengganti kartu bayangan. |
| **Segment** | `<x-ui.segment>` | Baris daftar itinerari dengan nomor indeks mono urut (`01`, `02`, `03`) dalam kontainer terbagi garis tipis (`divide-y`). |
| **Folio** | `<x-ui.folio>` | Kartu ringkasan finansial sticky kolom kanan, tempat utama diletakkannya tombol primer merah. |
| **Table Suite** | `<x-ui.table>`, `<x-ui.th>`, `<x-ui.tr>`, `<x-ui.td>` | Tabel pembukuan transaksi. Mendukung pengurutan kolom Livewire dan format angka rata kanan (`numeric`). |
| **Status Dot** | `<x-ui.status>` | Indikator status transaksi berupa titik lampu bulat 6px/8px (status aktif memiliki animasi denyut halus `animate-ping`). |
| **Button** | `<x-ui.button>` | Tombol terstandarisasi dengan 4 varian: `primary` (merah), `secondary` (garis netral), `ghost` (garis bawah teks), dan `danger`. |
| **Field** | `<x-ui.field>` | Pembungkus elemen input formulir (label kapital, pesan bantuan kanan atas, pesan validasi merah di bawah). |
| **Note** | `<x-ui.note>` | Kotak catatan atau peringatan kontekstual bergaris aksen samping (`border-s-4`). |
| **Empty State** | `<x-ui.empty>` | Tampilan saat data kosong yang memuat ikon informatif dan tombol ajakan aksi baru (*call-to-action*). |
| **Toolbar** | `<x-ui.search-input>`, `<x-ui.filter-select>` | Kontrol pencarian dan filter cepat pada toolbar tabel. |
| **Nav Group** | `<x-ui.nav-group>` | Grup menu sidebar admin yang collapsible dengan status buka/tutup tersimpan di `localStorage`. |

---

## 5. Batasan dan Larangan Desain Mutlak (*Banned Patterns*)

Untuk menjaga identitas visual dan konsistensi, aturan berikut ditegakkan secara ketat:
1. **Dilarang Sudut Membulat Berlebih:** Sudut hanya diizinkan `rounded-sm` (2px), `rounded` (4px), dan `rounded-md` (6px). Dilarang memakai `rounded-xl`, `rounded-2xl`, atau `rounded-full` (kecuali titik status dan foto avatar).
2. **Dilarang Efek Bayangan & Gradien Tebal:** Dilarang menggunakan `shadow-lg`, `shadow-xl`, `backdrop-blur`, atau kelas utilitas gradien warna (`bg-gradient-*`, `from-*`, `to-*`).
3. **Dilarang Warna Asing:** Dilarang memakai palet `slate-*`, `gray-*`, atau warna emas palsu `#D4AF37`. Gunakan selalu skala `neutral-*`.
4. **Wajib CSS Logical Properties untuk RTL:**
   - Dilarang: `ml-`, `mr-`, `pl-`, `pr-`, `text-left`, `text-right`, `border-l-`, `border-r-`, `left-`, `right-`.
   - Wajib: `ms-`, `me-`, `ps-`, `pe-`, `text-start`, `text-end`, `border-s-`, `border-e-`, `start-`, `end-`.
5. **Uji 3 Detik (*The 3-Second Test*):**
   Pengguna non-teknis yang membuka layar harus dapat memahami dalam 3 detik: (1) Di halaman mana ia berada, (2) Angka kunci utama apa yang ditampilkan, dan (3) Tombol apa yang harus diklik selanjutnya.
