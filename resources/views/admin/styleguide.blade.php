{{-- Dev-only living styleguide (route: /admin/styleguide, local env). Demo copy is intentionally hardcoded. --}}
<x-admin-layout>
    <x-slot name="header">Styleguide</x-slot>

    <x-ui.page-header
        title="Folio Penawaran — Keluarga Al-Thani"
        lede="Semua komponen x-ui.* dalam satu layar. Layout 8/4 asimetris, satu tombol merah, hairline bukan bayangan.">
        <x-slot name="eyebrow">
            <span>Cabang Bali</span><span class="text-neutral-300">/</span><span>Modul M5</span><span class="text-neutral-300">/</span>
            <x-ui.status status="draft">Draf</x-ui.status>
        </x-slot>
        <x-slot name="actions">
            <x-ui.button variant="ghost">Pratinjau PDF</x-ui.button>
            <x-ui.button>Simpan Draf</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- KPI strip: hairline stats, not four identical cards --}}
    <div class="mb-10 grid grid-cols-2 gap-x-6 gap-y-8 lg:grid-cols-4">
        <x-ui.stat label="Booking bulan ini" value="128" note="+12 vs Agustus" href="#" />
        <x-ui.stat label="Menunggu verifikasi" value="7" note="Finance Admin" />
        <x-ui.stat label="Nilai terkunci" value="SAR 412.300" note="kurs 4.210" />
        <x-ui.stat label="Tamu aktif" value="34" note="Bali · Jakarta" />
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <main class="space-y-8 lg:col-span-8">

            <x-ui.panel eyebrow="Itinerari" title="Urutan perjalanan" flush>
                <x-slot name="actions"><x-ui.button variant="ghost">+ Tambah segmen</x-ui.button></x-slot>
                <div class="divide-y divide-neutral-200">
                    <x-ui.segment :index="1" kicker="Hari 1 · Transit" title="Penjemputan bandara privat" meta="Mercedes Sprinter · Sopir wanita · Bahasa Arab" amount="SAR 1.200">
                        <x-slot name="actions"><x-ui.button variant="danger">Hapus</x-ui.button></x-slot>
                    </x-ui.segment>
                    <x-ui.segment :index="2" kicker="Hari 1–5 · Akomodasi" title="Mandapa, a Ritz-Carlton Reserve" meta="Two-Bedroom River Pool Villa · 4 malam · Half board" amount="SAR 18.400">
                        <x-slot name="actions"><x-ui.button variant="danger">Hapus</x-ui.button></x-slot>
                    </x-ui.segment>
                    <x-ui.segment :index="3" kicker="Hari 3 · Pengalaman" title="Sunrise Batur privat + sarapan" meta="Jeep 4x4 · 2 unit · Pemandu halal-friendly" amount="SAR 2.150" />
                </div>
            </x-ui.panel>

            <x-ui.table>
                <x-slot name="head">
                    <x-ui.th>Kode &amp; tamu</x-ui.th>
                    <x-ui.th>Paket</x-ui.th>
                    <x-ui.th>Status</x-ui.th>
                    <x-ui.th numeric>Total</x-ui.th>
                </x-slot>
                <x-ui.tr>
                    <x-ui.td sub="Keluarga Mansoor (5 pax)"><span class="font-mono font-semibold text-neutral-900">#BK-8841</span></x-ui.td>
                    <x-ui.td sub="12 Okt 2026 · Bali"><span class="font-medium text-neutral-900">Ubud Private Retreat</span></x-ui.td>
                    <x-ui.td><x-ui.status status="paid">Lunas</x-ui.status></x-ui.td>
                    <x-ui.td numeric>SAR 18.200</x-ui.td>
                </x-ui.tr>
                <x-ui.tr>
                    <x-ui.td sub="Sheikh Al-Thani (4 pax)"><span class="font-mono font-semibold text-neutral-900">#BK-8842</span></x-ui.td>
                    <x-ui.td sub="16 Jul 2026 · Jakarta"><span class="font-medium text-neutral-900">Jakarta Royal Itinerary</span></x-ui.td>
                    <x-ui.td><x-ui.status status="pending_payment">Menunggu bayar</x-ui.status></x-ui.td>
                    <x-ui.td numeric>SAR 34.200</x-ui.td>
                </x-ui.tr>
                <x-ui.tr>
                    <x-ui.td sub="Keluarga Rashid (6 pax)"><span class="font-mono font-semibold text-neutral-900">#BK-8843</span></x-ui.td>
                    <x-ui.td sub="02 Sep 2026 · Bali"><span class="font-medium text-neutral-900">Seminyak Beach Villas</span></x-ui.td>
                    <x-ui.td><x-ui.status status="cancelled">Batal</x-ui.status></x-ui.td>
                    <x-ui.td numeric>SAR 9.800</x-ui.td>
                </x-ui.tr>
            </x-ui.table>

            <div class="grid gap-8 md:grid-cols-2">
                <x-ui.panel eyebrow="Formulir" title="Field">
                    <div class="space-y-4">
                        <x-ui.field label="Nama tamu utama" for="g" hint="Sesuai paspor">
                            <input id="g" class="admin-input" value="Khalid Al-Thani">
                        </x-ui.field>
                        <x-ui.field label="Kurs terkunci" for="k" error="Kurs sudah kedaluwarsa, kunci ulang.">
                            <input id="k" class="admin-input border-danger font-mono" value="4.210">
                        </x-ui.field>
                        <x-ui.note>Perubahan kurs otomatis tercatat di Activity Log.</x-ui.note>
                        <x-ui.note tone="warning">Booking ini lintas cabang. Akses hanya untuk Super Admin.</x-ui.note>
                    </div>
                </x-ui.panel>

                <x-ui.empty title="Belum ada sopir dialokasikan" text="Segmen transit hari 1 masih kosong. Pilih dari sopir yang tersedia di Bali.">
                    <x-ui.button>Cari sopir tersedia</x-ui.button>
                </x-ui.empty>
            </div>

            <x-ui.panel eyebrow="Semua status" title="Badge PRD §4.6">
                <div class="flex flex-wrap gap-2">
                    @foreach(['draft','quoted','expired','confirmed','partially_paid','paid','in_progress','completed','cancelled'] as $s)
                        <x-ui.status :status="$s">{{ str_replace('_',' ',$s) }}</x-ui.status>
                    @endforeach
                </div>
            </x-ui.panel>
        </main>

        <aside class="lg:col-span-4">
            <x-ui.folio eyebrow="Rincian dokumen" title="Sintesis harga" ref="#FOL-2026-X8"
                :rows="[['Total harga modal','IDR 62.400.000'],['Margin musim (peak 35%)','+ IDR 21.840.000'],['Est. biaya gateway','+ 5,2%']]"
                total-label="Total penawaran" total="SAR 21.450"
                footnote="Kurs terkunci 1 SAR = Rp 4.210 · berlaku 7 hari">
                <x-ui.button variant="primary" class="w-full">Kunci &amp; terbitkan penawaran</x-ui.button>
                <p class="mt-3 text-center text-[10px] text-neutral-400">Satu-satunya tombol merah di layar ini.</p>
            </x-ui.folio>
        </aside>
    </div>
</x-admin-layout>
