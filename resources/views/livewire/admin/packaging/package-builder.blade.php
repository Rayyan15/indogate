<div>
    <x-ui.page-header :eyebrow="__('packaging.eyebrow')" :title="__('packaging.builder.title')" :lede="__('packaging.builder.lede')">
        <x-slot name="actions">
            <div class="flex items-center gap-2">
                <x-ui.button variant="ghost" :href="route('admin.packages.index')">
                    <svg class="h-4 w-4 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali</span>
                </x-ui.button>
            </div>
        </x-slot>
    </x-ui.page-header>

    <div
        x-data="packageBuilder({
            initialItems: @js($items),
        })"
        wire:ignore.self
        x-init="$watch('items', () => {})"
        class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_290px] xl:grid-cols-[minmax(0,1fr)_310px] items-start"
    >
        {{-- Left column: builder — Alpine owns $data.items, no server round-trip per row edit --}}
        <div class="min-w-0 space-y-5" wire:ignore.self>

            {{-- Card 1: Basic Information --}}
            <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6 space-y-6 shadow-sm">
                <div class="border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">{{ __('packaging.builder.basic_info') }}</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ __('packaging.builder.basic_info_desc') }}</p>
                </div>

                {{-- Multilingual Package Name --}}
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                        {{ __('packaging.builder.meta_title') }} <span class="text-red-600">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-xs font-bold text-neutral-400">EN</span>
                                <input type="text" wire:model="name.en" placeholder="Package title in English" class="admin-input ps-10">
                            </div>
                            @error('name.en') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-xs font-bold text-neutral-400">ID</span>
                                <input type="text" wire:model="name.id" placeholder="Nama paket dalam Bahasa Indonesia" class="admin-input ps-10">
                            </div>
                            @error('name.id') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <div class="relative">
                                <span class="absolute inset-y-0 start-0 flex items-center ps-3 text-xs font-bold text-neutral-400">AR</span>
                                <input type="text" wire:model="name.ar" placeholder="اسم الباقة بالعربية" dir="rtl" class="admin-input ps-10">
                            </div>
                            @error('name.ar') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- Base Pax & Duration Days --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('packaging.builder.base_pax') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative">
                            <input type="number" min="1" wire:model="base_pax" class="admin-input pe-16 font-mono">
                            <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-xs text-neutral-400 font-medium">Pax</span>
                        </div>
                        <p class="mt-1 text-[11px] text-neutral-500">Standar kapasitas acuan tamu dasar (default: 2 pax).</p>
                        @error('base_pax') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('packaging.builder.duration_days') }}
                        </label>
                        <div class="mt-1 relative">
                            <input type="number" min="1" wire:model="duration_days" class="admin-input pe-14 font-mono" placeholder="3">
                            <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-xs text-neutral-400 font-medium">Hari</span>
                        </div>
                        <p class="mt-1 text-[11px] text-neutral-500">Total rentang hari kegiatan pada jadwal tur (misal: 3 hari).</p>
                        @error('duration_days') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Template & Publication Option Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <label class="flex items-start gap-3 rounded-lg border border-neutral-200 p-3.5 hover:bg-neutral-50/50 transition cursor-pointer">
                        <input type="checkbox" wire:model="is_template" class="mt-0.5 h-4 w-4 rounded accent-red-600">
                        <div>
                            <span class="block text-sm font-semibold text-neutral-900">{{ __('packaging.builder.is_template') }}</span>
                            <span class="block text-xs text-neutral-500 mt-0.5">{{ __('packaging.builder.template_hint') }}</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-neutral-200 p-3.5 hover:bg-neutral-50/50 transition cursor-pointer">
                        <input type="checkbox" wire:model="is_published" class="mt-0.5 h-4 w-4 rounded accent-red-600">
                        <div>
                            <span class="block text-sm font-semibold text-neutral-900">{{ __('packaging.builder.is_published') }}</span>
                            <span class="block text-xs text-neutral-500 mt-0.5">{{ __('packaging.builder.publish_hint') }}</span>
                        </div>
                    </label>
                </div>

                @if($durationWarning)
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 flex items-center gap-2.5 text-xs text-amber-800">
                        <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $durationWarning }}</span>
                    </div>
                @endif

                {{-- Brochure File Section --}}
                @if($packageId)
                    <div class="pt-4 border-t border-neutral-100">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700 mb-2">
                            {{ __('packaging.builder.brochure') }}
                        </label>
                        @if($brochure_path)
                            <div class="flex items-center justify-between rounded-lg border border-neutral-200 bg-neutral-50 p-3">
                                <div class="flex items-center gap-2 text-sm text-neutral-800">
                                    <svg class="h-5 w-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <a href="{{ Storage::disk('public')->url($brochure_path) }}" target="_blank" class="font-medium text-blue-600 hover:underline">
                                        {{ __('packaging.builder.view_brochure') }}
                                    </a>
                                </div>
                                <button type="button" wire:click="removeBrochure" class="text-xs font-medium text-red-600 hover:text-red-800 hover:underline">
                                    {{ __('packaging.builder.remove_brochure') }}
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <input type="file" wire:model="brochure" accept=".pdf,.jpg,.jpeg,.png" class="admin-input flex-1 text-xs">
                                <x-ui.button variant="secondary" type="button" wire:click="uploadBrochure" wire:loading.attr="disabled">
                                    {{ __('packaging.builder.upload_brochure') }}
                                </x-ui.button>
                            </div>
                            <p class="mt-1 text-[11px] text-neutral-400">{{ __('packaging.builder.brochure_hint') }}</p>
                        @endif
                        @error('brochure') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            {{-- Card 2: Itinerary Components Builder --}}
            <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-4 sm:p-5 space-y-4 shadow-sm">
                <div class="border-b border-neutral-100 pb-3">
                    <h3 class="text-base font-bold text-neutral-900">{{ __('packaging.builder.itinerary_components') }}</h3>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ __('packaging.builder.itinerary_desc') }}</p>
                </div>

                {{-- Informational Logic Guide --}}
                <div class="rounded-lg bg-blue-50/70 border border-blue-200/80 p-4 text-xs text-blue-900 space-y-2">
                    <div class="flex items-center gap-2 font-semibold text-blue-950">
                        <svg class="h-4 w-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ __('packaging.builder.guide_title') }}</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1 min-w-0">
                        <div class="bg-neutral-0/80 p-2.5 rounded border border-blue-100 min-w-0">
                            <p class="font-semibold text-neutral-900">1. Jadwal Hari (Itinerari)</p>
                            <p class="mt-0.5 text-[11px] text-neutral-600 leading-relaxed">0 = Hari pertama kedatangan, 1 = Hari kedua, dst. Rentang 0 s/d 2 berarti mencakup hari ke-1 sampai hari ke-3.</p>
                        </div>
                        <div class="bg-neutral-0/80 p-2.5 rounded border border-blue-100 min-w-0">
                            <p class="font-semibold text-neutral-900">2. Jumlah Unit (Qty)</p>
                            <p class="mt-0.5 text-[11px] text-neutral-600 leading-relaxed">Banyaknya kamar hotel yang dipesan, tiket wisata per rombongan, atau armada kendaraan yang disewa.</p>
                        </div>
                        <div class="bg-neutral-0/80 p-2.5 rounded border border-blue-100 min-w-0">
                            <p class="font-semibold text-neutral-900">3. Durasi Menginap (Malam)</p>
                            <p class="mt-0.5 text-[11px] text-neutral-600 leading-relaxed">Diisi khusus kamar hotel (cth: 2 malam). Kosongkan atau isi 0 untuk aktivitas atau transportasi satu hari.</p>
                        </div>
                    </div>
                </div>

                @php
                    $roomItems = ($availableInventory ?? collect())->where('type.value', 'room');
                    $vehicleItems = ($availableInventory ?? collect())->where('type.value', 'vehicle');
                    $ticketItems = ($availableInventory ?? collect())->where('type.value', 'ticket');
                    $activityItems = ($availableInventory ?? collect())->where('type.value', 'activity');
                @endphp

                {{-- Major Categories Quick Recommendations --}}
                <div x-data="{ activeCat: 'all' }" class="space-y-3.5 pt-2 pb-1">
                    <div class="flex items-center justify-between flex-wrap gap-2.5">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-800">
                                Rekomendasi Komponen (Berdasarkan Kategori)
                            </h4>
                            <p class="text-[11px] text-neutral-500 mt-0.5">Pilih langsung dari inventaris utama di bawah tanpa perlu mengetik pencarian.</p>
                        </div>
                        <div class="inline-flex items-center p-1 bg-neutral-100 rounded-lg border border-neutral-200/80 flex-wrap gap-1 text-xs">
                            <button
                                type="button"
                                x-on:click="activeCat = 'all'"
                                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
                                :class="activeCat === 'all' ? 'bg-neutral-0 text-neutral-900 shadow-2xs font-bold' : 'text-neutral-600 hover:text-neutral-900'"
                            >
                                Semua ({{ ($availableInventory ?? collect())->count() }})
                            </button>
                            <button
                                type="button"
                                x-on:click="activeCat = 'room'"
                                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
                                :class="activeCat === 'room' ? 'bg-indigo-600 text-white shadow-2xs font-bold' : 'text-neutral-600 hover:text-indigo-700'"
                            >
                                Hotel & Kamar ({{ $roomItems->count() }})
                            </button>
                            <button
                                type="button"
                                x-on:click="activeCat = 'vehicle'"
                                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
                                :class="activeCat === 'vehicle' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'text-neutral-600 hover:text-emerald-700'"
                            >
                                Driver & Armada ({{ $vehicleItems->count() }})
                            </button>
                            <button
                                type="button"
                                x-on:click="activeCat = 'ticket'"
                                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
                                :class="activeCat === 'ticket' ? 'bg-amber-600 text-white shadow-2xs font-bold' : 'text-neutral-600 hover:text-amber-700'"
                            >
                                Flight & Tiket ({{ $ticketItems->count() }})
                            </button>
                            <button
                                type="button"
                                x-on:click="activeCat = 'activity'"
                                class="px-3 py-1 rounded-md text-xs font-medium transition cursor-pointer"
                                :class="activeCat === 'activity' ? 'bg-blue-600 text-white shadow-2xs font-bold' : 'text-neutral-600 hover:text-blue-700'"
                            >
                                Tur & Aktivitas ({{ $activityItems->count() }})
                            </button>
                        </div>
                    </div>

                    {{-- Quick Recommendation Cards Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-3 max-h-80 overflow-y-auto p-3 bg-neutral-50/80 rounded-xl border border-neutral-200 min-w-0">
                        @foreach($availableInventory ?? [] as $inv)
                            @php
                                $typeCategory = $inv->type->value;
                                $invName = $inv->getTranslation('name', app()->getLocale(), false) ?? $inv->getTranslation('name', 'en', false);
                                $badgeStyle = match($typeCategory) {
                                    'room' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'vehicle' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'ticket' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    default => 'bg-blue-50 text-blue-700 border-blue-200',
                                };
                                $categoryLabel = match($typeCategory) {
                                    'room' => 'Hotel / Room',
                                    'vehicle' => 'Driver & Armada',
                                    'ticket' => 'Flight & Tiket',
                                    default => 'Aktivitas & Tur',
                                };
                            @endphp
                            <div
                                x-show="activeCat === 'all' || activeCat === '{{ $typeCategory }}'"
                                x-transition
                                class="flex flex-col justify-between p-3.5 rounded-xl bg-neutral-0 border border-neutral-200/90 shadow-2xs hover:border-red-300 hover:shadow-xs transition min-w-0"
                            >
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider border {{ $badgeStyle }}">
                                            {{ $categoryLabel }}
                                        </span>
                                        <span class="text-[10px] font-mono text-neutral-400">ID: {{ $inv->id }}</span>
                                    </div>
                                    <h5 class="font-bold text-sm text-neutral-900 leading-snug line-clamp-1" title="{{ $invName }}">
                                        {{ $invName }}
                                    </h5>
                                    <p class="text-xs text-neutral-500 font-medium truncate mt-0.5">
                                        {{ $inv->partner?->name ?? 'Indogate Mitra' }}
                                    </p>
                                </div>

                                <div class="mt-3.5 pt-2.5 border-t border-neutral-100 flex items-center justify-between">
                                    <span class="text-[11px] text-neutral-400 font-medium">Klik untuk memasukkan</span>
                                    <button
                                        type="button"
                                        x-on:click="addItem({{ $inv->id }}, @js($invName), '{{ $typeCategory }}', @js($inv->partner?->name ?? ''))"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-50 hover:bg-red-600 px-3 py-1.5 text-xs font-semibold text-red-700 hover:text-white transition shadow-2xs cursor-pointer"
                                        title="Tambah ke Paket"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        <span>Tambah</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Component Search & Picker --}}
                <div class="relative pt-1">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700 mb-1.5">
                        Atau Cari Komponen Spesifik
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-neutral-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.400ms="componentSearch"
                            placeholder="{{ __('packaging.builder.component_picker_placeholder') }}"
                            class="admin-input ps-9 pe-9 text-xs"
                        >
                        @if($componentSearch !== '')
                            <button
                                type="button"
                                wire:click="$set('componentSearch', '')"
                                class="absolute inset-y-0 end-0 flex items-center pe-3 text-neutral-400 hover:text-neutral-600"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    @if($componentSearch !== '' && $this->componentResults->isNotEmpty())
                        <div class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-neutral-200 bg-neutral-0 shadow-lg divide-y divide-neutral-100">
                            @foreach($this->componentResults as $result)
                                <button
                                    type="button"
                                    x-on:click="addItem({{ $result->id }}, @js($result->getTranslation('name', app()->getLocale(), false) ?? $result->getTranslation('name', 'en', false)), '{{ $result->type->value }}', @js($result->partner?->name ?? '')); $wire.set('componentSearch', '')"
                                    class="flex w-full items-center justify-between px-4 py-2.5 text-start text-xs hover:bg-neutral-50 transition"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex items-center rounded bg-neutral-100 px-2 py-0.5 text-[10px] font-mono font-medium uppercase text-neutral-700">
                                            {{ __('catalog.item.'.$result->type->value) }}
                                        </span>
                                        <span class="font-medium text-neutral-900">
                                            {{ $result->getTranslation('name', app()->getLocale(), false) ?? $result->getTranslation('name', 'en', false) }}
                                        </span>
                                    </div>
                                    <span class="inline-flex items-center text-xs font-semibold text-red-600 hover:text-red-700">
                                        + Tambahkan
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @elseif($componentSearch !== '' && $this->componentResults->isEmpty())
                        <div class="absolute z-30 mt-1 w-full rounded-lg border border-neutral-200 bg-neutral-0 p-4 shadow-lg text-center text-xs text-neutral-500">
                            Tidak ada item inventaris yang cocok dengan "{{ $componentSearch }}".
                        </div>
                    @endif
                </div>

                {{-- Empty state when no items --}}
                <div x-show="items.length === 0" class="py-10 text-center rounded-lg border-2 border-dashed border-neutral-200 bg-neutral-50/50">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h4 class="mt-3 text-sm font-semibold text-neutral-800">{{ __('packaging.builder.no_component') }}</h4>
                    <p class="mt-1 text-xs text-neutral-500 max-w-sm mx-auto">Klik tombol <strong>+ Tambah</strong> pada kartu rekomendasi kategori di atas (Hotel, Driver, Flight, atau Aktivitas) untuk memulai penyusunan paket.</p>
                </div>

                {{-- Items Table --}}
                <div x-show="items.length > 0" wire:ignore class="overflow-x-auto rounded-lg border border-neutral-200 shadow-2xs">
                    <table class="w-full text-xs text-center">
                        <thead class="bg-neutral-50/80 border-b border-neutral-200/80 text-neutral-500 font-semibold uppercase text-[10px] tracking-wider">
                            <tr>
                                <th scope="col" class="py-2.5 px-1.5 w-7 text-center text-neutral-400 font-normal">#</th>
                                <th scope="col" class="py-2.5 px-2 text-center w-[160px] max-w-[170px]">Komponen Layanan</th>
                                <th scope="col" class="py-2.5 px-1.5 text-center w-[115px]">Jadwal Hari</th>
                                <th scope="col" class="py-2.5 px-1 text-center w-[76px]">Jumlah</th>
                                <th scope="col" class="py-2.5 px-1 text-center w-[76px]">Menginap</th>
                                <th scope="col" class="py-2.5 px-2 text-center w-[125px]">Subtotal</th>
                                <th scope="col" class="py-2.5 px-1 text-center w-7"></th>
                            </tr>
                        </thead>
                        <tbody wire:ignore class="divide-y divide-neutral-100 bg-neutral-0">
                            <template x-for="(row, index) in items" :key="row.id">
                                <tr class="hover:bg-neutral-50/80 transition">
                                    {{-- Index --}}
                                    <td class="py-2.5 px-1.5 text-center align-middle">
                                        <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-neutral-100 font-mono text-[10px] font-semibold text-neutral-500" x-text="index + 1"></span>
                                    </td>

                                    {{-- Component Name --}}
                                    <td class="py-2.5 px-2 text-center align-middle max-w-[170px]">
                                        <div class="inline-flex flex-col items-center justify-center text-center max-w-full">
                                            <div class="flex items-center justify-center gap-1.5 mb-0.5">
                                                <template x-if="isRoom(row)">
                                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-indigo-50 text-indigo-700 border border-indigo-200/80">
                                                        Hotel
                                                    </span>
                                                </template>
                                                <template x-if="row.type === 'vehicle'">
                                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                                        Armada
                                                    </span>
                                                </template>
                                                <template x-if="row.type === 'ticket'">
                                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200/80">
                                                        Tiket
                                                    </span>
                                                </template>
                                                <template x-if="!isRoom(row) && row.type !== 'vehicle' && row.type !== 'ticket'">
                                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase bg-blue-50 text-blue-700 border border-blue-200/80">
                                                        Tur
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="font-semibold text-neutral-900 text-xs sm:text-sm leading-tight truncate max-w-[160px]" :title="row.name" x-text="row.name"></div>
                                            <div class="flex items-center justify-center gap-1 mt-0.5 text-[10px] text-neutral-500 max-w-[160px]">
                                                <span x-show="row.partner_name" x-text="row.partner_name" class="truncate font-medium text-neutral-600 max-w-[105px]" :title="row.partner_name"></span>
                                                <span x-show="row.partner_name" class="text-neutral-300">&bull;</span>
                                                <span class="font-mono text-neutral-400 shrink-0" x-text="'ID: ' + row.inventory_item_id"></span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Day Schedule & Duration --}}
                                    <td class="py-2.5 px-1 text-center align-middle">
                                        <div class="inline-flex flex-col items-center justify-center gap-1">
                                            {{-- Hari Ke berapa --}}
                                            <div class="inline-flex items-center gap-1">
                                                <span class="text-[10px] text-neutral-400 font-medium select-none">Mulai: H-</span>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :value="row.day_from + 1"
                                                    x-on:input="
                                                        let val = parseInt($event.target.value);
                                                        if (!isNaN(val) && val >= 1) {
                                                            let dur = Math.max(0, row.day_to - row.day_from);
                                                            row.day_from = val - 1;
                                                            row.day_to = row.day_from + dur;
                                                            if (isRoom(row)) {
                                                                row.nights = Math.max(1, row.day_to - row.day_from);
                                                            }
                                                            recalculate();
                                                        }
                                                    "
                                                    x-on:blur="if (!$event.target.value || parseInt($event.target.value) < 1) { $event.target.value = row.day_from + 1; }"
                                                    class="w-6 h-5 text-center font-bold text-xs text-neutral-800 p-0 rounded border border-neutral-200 bg-neutral-50/60 shadow-2xs [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:border-red-500 focus:outline-none"
                                                    title="Mulai pada hari perjalanan ke berapa"
                                                >
                                            </div>

                                            {{-- Durasi --}}
                                            <div class="inline-flex items-center gap-1">
                                                <span class="text-[10px] text-neutral-400 font-medium select-none">Durasi:</span>
                                                <div class="inline-flex items-center rounded border border-neutral-200 bg-white shadow-2xs overflow-hidden">
                                                    <button
                                                        type="button"
                                                        x-on:click="
                                                            let curDur = (row.day_to - row.day_from) + 1;
                                                            if (curDur > 1) {
                                                                row.day_to = row.day_from + (curDur - 2);
                                                                if (isRoom(row)) {
                                                                    row.nights = Math.max(1, row.day_to - row.day_from);
                                                                }
                                                                recalculate();
                                                            }
                                                        "
                                                        class="h-5 w-4 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 text-xs font-bold transition select-none cursor-pointer"
                                                        title="Kurangi Hari"
                                                    >−</button>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        :value="(row.day_to - row.day_from) + 1"
                                                        x-on:input="
                                                            let val = parseInt($event.target.value);
                                                            if (!isNaN(val) && val >= 1) {
                                                                row.day_to = row.day_from + (val - 1);
                                                                if (isRoom(row)) {
                                                                    row.nights = Math.max(1, row.day_to - row.day_from);
                                                                }
                                                                recalculate();
                                                            }
                                                        "
                                                        x-on:blur="if (!$event.target.value || parseInt($event.target.value) < 1) { $event.target.value = (row.day_to - row.day_from) + 1; }"
                                                        class="w-5 h-5 text-center font-bold text-xs text-neutral-900 p-0 border-x border-neutral-100 outline-none bg-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    >
                                                    <button
                                                        type="button"
                                                        x-on:click="
                                                            let curDur = (row.day_to - row.day_from) + 1;
                                                            row.day_to = row.day_from + curDur;
                                                            if (isRoom(row)) {
                                                                row.nights = Math.max(1, row.day_to - row.day_from);
                                                            }
                                                            recalculate();
                                                        "
                                                        class="h-5 w-4 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 text-xs font-bold transition select-none cursor-pointer"
                                                        title="Tambah Hari"
                                                    >+</button>
                                                </div>
                                                <span class="text-[9px] text-neutral-400 font-medium select-none">hr</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Qty --}}
                                    <td class="py-2.5 px-1 text-center align-middle">
                                        <div class="inline-flex flex-col items-center justify-center">
                                            <div class="inline-flex items-center rounded border border-neutral-200 bg-white shadow-2xs overflow-hidden">
                                                <button
                                                    type="button"
                                                    x-on:click="if (row.qty > 1) { row.qty--; recalculate(); }"
                                                    class="h-6 w-5 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 text-xs font-bold transition select-none cursor-pointer"
                                                    title="Kurangi"
                                                >−</button>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    x-model.number="row.qty"
                                                    x-on:change="if (!row.qty || row.qty < 1) row.qty = 1; recalculate();"
                                                    class="w-6 h-6 text-center font-bold text-xs text-neutral-900 p-0 border-x border-neutral-100 outline-none bg-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                >
                                                <button
                                                    type="button"
                                                    x-on:click="row.qty++; recalculate();"
                                                    class="h-6 w-5 flex items-center justify-center text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 text-xs font-bold transition select-none cursor-pointer"
                                                    title="Tambah"
                                                >+</button>
                                            </div>
                                            <span class="mt-0.5 text-[9px] font-medium text-neutral-400 capitalize tracking-tight" x-text="getQtyUnit(row)"></span>
                                        </div>
                                    </td>

                                    {{-- Nights --}}
                                    <td class="py-2.5 px-1 text-center align-middle">
                                        <template x-if="isRoom(row)">
                                            <div class="inline-flex flex-col items-center justify-center">
                                                <div class="inline-flex items-center rounded border border-indigo-200 bg-indigo-50/40 shadow-2xs overflow-hidden">
                                                    <button
                                                        type="button"
                                                        x-on:click="if (row.nights > 1) { row.nights--; recalculate(); }"
                                                        class="h-6 w-5 flex items-center justify-center text-indigo-700 hover:text-indigo-950 hover:bg-indigo-100 text-xs font-bold transition select-none cursor-pointer"
                                                        title="Kurangi Malam"
                                                    >−</button>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        x-model.number="row.nights"
                                                        x-on:change="if (!row.nights || row.nights < 1) row.nights = 1; recalculate();"
                                                        class="w-6 h-6 text-center font-bold text-xs text-indigo-950 p-0 border-x border-indigo-100 outline-none bg-transparent [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    >
                                                    <button
                                                        type="button"
                                                        x-on:click="row.nights = (row.nights || 0) + 1; recalculate();"
                                                        class="h-6 w-5 flex items-center justify-center text-indigo-700 hover:text-indigo-950 hover:bg-indigo-100 text-xs font-bold transition select-none cursor-pointer"
                                                        title="Tambah Malam"
                                                    >+</button>
                                                </div>
                                                <span class="mt-0.5 text-[9px] font-medium text-indigo-500">malam</span>
                                            </div>
                                        </template>
                                        <template x-if="!isRoom(row)">
                                            <span class="inline-flex items-center text-neutral-300 font-mono text-xs select-none">—</span>
                                        </template>
                                    </td>

                                    {{-- Subtotal Price in row --}}
                                    <td class="py-2.5 px-2 text-center align-middle whitespace-nowrap">
                                        <div class="inline-flex flex-col items-center justify-center">
                                            <template x-if="$wire.summary && $wire.summary.items && $wire.summary.items[index]">
                                                <div>
                                                    <div class="font-mono text-xs sm:text-sm font-bold text-neutral-900 tracking-tight" x-text="$wire.summary.items[index].display_price_formatted || '—'"></div>
                                                    <template x-if="$wire.summary.items[index].rate_missing">
                                                        <span class="inline-block text-[9px] font-medium text-red-600 bg-red-50 px-1 py-0.2 rounded border border-red-200 mt-0.5">Tarif belum ada</span>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="!($wire.summary && $wire.summary.items && $wire.summary.items[index])">
                                                <span class="text-neutral-300 font-mono text-xs">—</span>
                                            </template>
                                        </div>
                                    </td>

                                    {{-- Delete Action --}}
                                    <td class="py-2.5 px-1 text-center align-middle">
                                        <button
                                            type="button"
                                            x-on:click="removeItem(index)"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded text-neutral-400 hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                                            title="Hapus Komponen"
                                            aria-label="Hapus Komponen"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Action Reminder Bar --}}
                <div class="flex items-center justify-between pt-2">
                    <p class="text-xs text-neutral-500">
                        Perubahan komponen langsung dihitung di peramban. Klik <strong>Simpan Paket</strong> saat susunan selesai.
                    </p>
                    <div class="flex items-center gap-2">
                        <x-ui.button variant="secondary" type="button" x-on:click="recalculate()">
                            <svg class="h-3.5 w-3.5 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ __('packaging.builder.recalculate') }}</span>
                        </x-ui.button>
                        <x-ui.button variant="primary" type="button" x-on:click="$wire.call('recalculate', items, $wire.current_pax).then(() => $wire.save())">
                            <svg class="h-3.5 w-3.5 me-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ __('packaging.builder.save') }}</span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column: server-authoritative live pricing simulation engine --}}
        <div class="w-full min-w-0 space-y-6">
            <div class="lg:sticky lg:top-6 space-y-6">

                {{-- Card: Simulator Parameters --}}
                <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-5 shadow-sm space-y-4">
                    <div class="border-b border-neutral-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100 text-red-600">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-bold text-neutral-900">{{ __('packaging.builder.simulator_title') }}</h3>
                        </div>
                        <p class="text-[11px] text-neutral-500 mt-1">{{ __('packaging.builder.simulator_desc') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('packaging.builder.preview_date') }}
                            </label>
                            <input type="date" wire:model="preview_date" class="admin-input text-xs py-2 font-mono">
                            <span class="text-[10px] text-neutral-400 mt-0.5 block">Menentukan tarif musim (peak/low season)</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('packaging.builder.current_pax') }}
                            </label>
                            <div class="relative">
                                <input type="number" min="1" wire:model.live.debounce.500ms="current_pax" class="admin-input text-xs py-2 pe-12 font-mono">
                                <span class="absolute inset-y-0 end-0 flex items-center pe-3 text-[11px] text-neutral-400 font-medium">Pax</span>
                            </div>
                            <span class="text-[10px] text-neutral-400 mt-0.5 block">Kalkulasi pembagian biaya per tamu</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('packaging.builder.channel') }}
                            </label>
                            <select wire:model="channel" class="admin-input text-xs py-2">
                                <option value="bank_transfer">{{ __('pricing.channel_cost.bank_transfer') }}</option>
                                <option value="international_card">{{ __('pricing.channel_cost.international_card') }}</option>
                            </select>
                            <span class="text-[10px] text-neutral-400 mt-0.5 block">Biaya channel fee payment gateway</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('packaging.builder.display_currency') }}
                            </label>
                            <select wire:model.live="display_currency" class="admin-input text-xs py-2 font-mono font-semibold">
                                <option value="IDR">IDR — Indonesian Rupiah</option>
                                <option value="USD">USD — US Dollar</option>
                                <option value="AED">AED — UAE Dirham</option>
                            </select>
                            <span class="text-[10px] text-neutral-400 mt-0.5 block">Mata uang konversi harga jual</span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button
                            type="button"
                            x-on:click="recalculate()"
                            class="w-full flex items-center justify-center gap-2 rounded-md bg-neutral-100 hover:bg-neutral-200 text-neutral-800 px-3 py-2 text-xs font-semibold transition"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ __('packaging.builder.recalculate') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Card: Grand Total & Margin Breakdown (Itemized list removed so components are only edited on the left table) --}}
                <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-neutral-100 text-neutral-600">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-bold text-neutral-900">Total Harga Paket</h3>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>LIVE</span>
                        </span>
                    </div>

                    @if($summary)
                        <div class="space-y-3">
                            {{-- Grand Display Sell Price Banner --}}
                            <div class="rounded-lg bg-neutral-900 p-4 text-white shadow-sm border border-neutral-800">
                                <div class="flex items-center justify-between text-neutral-400">
                                    <span class="text-[10px] uppercase tracking-wider font-semibold">
                                        {{ __('packaging.builder.display_price') }}
                                    </span>
                                    @if($current_pax > 1)
                                        <span class="text-[10px] font-medium text-neutral-300 bg-neutral-800 px-1.5 py-0.5 rounded">
                                            {{ $current_pax }} Tamu
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-2 text-lg sm:text-xl font-bold font-mono tracking-tight text-white leading-tight break-all sm:break-normal">
                                    {{ $summary['grand']['display_price_formatted'] }}
                                </div>
                                @if($current_pax > 1)
                                    <div class="mt-1 text-[11px] text-neutral-400">
                                        Total estimasi untuk {{ $current_pax }} tamu
                                    </div>
                                @endif
                            </div>

                            {{-- Admin Cost & Margin Details --}}
                            @if(array_key_exists('cost_total', $summary['grand']))
                                <div class="rounded-lg bg-neutral-50 p-3 space-y-2 text-xs border border-neutral-200/80">
                                    <div class="flex items-center justify-between text-neutral-600">
                                        <span class="font-medium">{{ __('packaging.builder.cost_total') }}</span>
                                        <span class="font-mono font-semibold text-neutral-900">IDR {{ number_format($summary['grand']['cost_total']) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-neutral-600">
                                        <span class="font-medium">{{ __('packaging.builder.margin_minor') }}</span>
                                        <span class="font-mono font-bold text-emerald-600">+ IDR {{ number_format($summary['grand']['margin_minor']) }}</span>
                                    </div>
                                    @if(isset($summary['grand']['channel_cost']) && $summary['grand']['channel_cost'] > 0)
                                        <div class="flex items-center justify-between text-neutral-600">
                                            <span class="font-medium">{{ __('packaging.builder.channel_cost') }}</span>
                                            <span class="font-mono font-medium text-neutral-800">IDR {{ number_format($summary['grand']['channel_cost']) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="py-6 text-center text-xs text-neutral-400">
                            <svg class="h-8 w-8 mx-auto mb-2 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                            </svg>
                            <p>Klik tombol <strong>Hitung Ulang</strong> untuk menampilkan kalkulasi total harga paket.</p>
                        </div>
                    @endif

                    {{-- Save Button in card --}}
                    <div class="pt-2 border-t border-neutral-100">
                        <button
                            type="button"
                            x-on:click="$wire.call('recalculate', items, $wire.current_pax).then(() => $wire.save())"
                            class="w-full flex items-center justify-center gap-2 rounded-md bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 text-sm font-semibold transition shadow-sm cursor-pointer"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ __('packaging.builder.save') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Card: Export PDF (if editing an existing package) --}}
                @if($packageId)
                    <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-5 shadow-sm space-y-3" @if($exportPending) wire:poll.3s="checkExportReady" @endif>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">{{ __('packaging.builder.export_pdf') }}</h4>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <x-ui.button variant="secondary" type="button" wire:click="exportPdf('en')" class="text-xs py-1.5 justify-center">English</x-ui.button>
                            <x-ui.button variant="secondary" type="button" wire:click="exportPdf('id')" class="text-xs py-1.5 justify-center">Indonesia</x-ui.button>
                            <x-ui.button variant="secondary" type="button" wire:click="exportPdf('ar')" class="text-xs py-1.5 justify-center">العربية</x-ui.button>
                        </div>
                        @if($exportPending)
                            <div class="flex items-center gap-2 text-xs text-neutral-500 bg-neutral-50 p-2.5 rounded border border-neutral-200">
                                <div class="h-3 w-3 animate-spin rounded-full border-2 border-red-600 border-t-transparent"></div>
                                <span>{{ __('packaging.builder.export_pending') }}</span>
                            </div>
                        @endif
                        @if($downloadUrl)
                            <div class="pt-1">
                                <a href="{{ $downloadUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>{{ __('packaging.builder.download') }}</span>
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('packageBuilder', ({ initialItems }) => ({
        items: initialItems || [],
        recalcTimer: null,
        init() {
            this.items.forEach(row => {
                if (this.isRoom(row) && (!row.nights || row.nights < 1)) {
                    row.nights = Math.max(1, (row.day_to || 0) - (row.day_from || 0));
                }
            });
        },
        addItem(inventoryItemId, name, type = '', partnerName = '') {
            const isRoomItem = type === 'room' || /kamar|room|suite|villa|hotel|resort/i.test(name || '');
            const newId = 'tmp_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);
            this.items.push({
                id: newId,
                inventory_item_id: inventoryItemId,
                name: name,
                type: type || (isRoomItem ? 'room' : 'activity'),
                partner_name: partnerName || '',
                day_from: 0,
                day_to: 0,
                qty: 1,
                nights: isRoomItem ? 1 : null,
                sort_order: this.items.length,
            });
            this.recalculate();
        },
        removeItem(index) {
            this.items.splice(index, 1);
            this.items.forEach((row, i) => row.sort_order = i);
            this.recalculate();
        },
        isRoom(row) {
            if (!row) return false;
            if (row.type === 'room') return true;
            if (row.type && row.type !== 'room') return false;
            return /kamar|room|suite|villa|hotel|resort/i.test(row.name || '');
        },
        getQtyUnit(row) {
            if (!row) return 'Unit';
            if (this.isRoom(row)) return 'Kamar';
            if (row.type === 'vehicle' || /driver|armada|mobil|van|bus|hiace|innova|alphard/i.test(row.name || '')) return 'Mobil';
            if (row.type === 'ticket' || /tiket|ticket|flight|pesawat/i.test(row.name || '')) return 'Tiket';
            return 'Unit';
        },
        recalculate(immediate = false) {
            if (this.recalcTimer) {
                clearTimeout(this.recalcTimer);
                this.recalcTimer = null;
            }
            if (immediate) {
                return this.$wire.call('recalculate', this.items, this.$wire.current_pax);
            }
            this.recalcTimer = setTimeout(() => {
                this.$wire.call('recalculate', this.items, this.$wire.current_pax);
            }, 250);
        },
    }));
</script>
@endscript
