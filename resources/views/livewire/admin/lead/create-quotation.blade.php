<div>
    <h3 class="font-display text-sm font-medium text-neutral-900">{{ __('quotation.form.title') }}</h3>

    <form wire:submit="generate" class="mt-3 grid grid-cols-2 gap-3">
        <div class="col-span-2">
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('quotation.form.package') }}</label>
            <select wire:model="package_id" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                @endforeach
            </select>
            @error('package_id') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('quotation.form.pax') }}</label>
            <input type="number" min="1" wire:model="pax" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            @error('pax') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('quotation.form.preview_date') }}</label>
            <input type="date" wire:model="preview_date" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            @error('preview_date') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('quotation.form.currency') }}</label>
            <input type="text" maxlength="3" wire:model="currency" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm uppercase">
            @error('currency') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('quotation.form.channel') }}</label>
            <select wire:model="channel" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                @foreach ($channels as $c)
                    <option value="{{ $c->value }}">{{ $c->value }}</option>
                @endforeach
            </select>
            @error('channel') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="col-span-2">
            <x-ui.button type="submit" variant="primary">{{ __('quotation.form.generate') }}</x-ui.button>
        </div>
    </form>

    @if($generatedLink)
        <div class="mt-4 rounded border border-success/20 bg-success/10 p-3 text-xs text-success">
            {{ __('quotation.form.generated') }}
            <a href="{{ $generatedLink }}" target="_blank" class="break-all underline">{{ $generatedLink }}</a>
        </div>
    @endif

    @if($quotations->isNotEmpty())
        <div class="mt-6">
            <h4 class="text-xs font-medium text-neutral-600">{{ __('quotation.form.history') }}</h4>
            <ul class="mt-2 space-y-1 text-xs text-neutral-600">
                @foreach($quotations as $q)
                    <li class="border-b border-neutral-100 py-1">
                        <div class="flex items-center justify-between">
                            <span>{{ $q->package->name }} · {{ $q->currency }} · <x-ui.status :status="$q->status->value">{{ $q->status->value }}</x-ui.status></span>
                            <span class="flex items-center gap-2">
                                <a href="{{ route('quotations.public-show', ['locale' => $lead->locale, 'quotation' => $q]) }}" target="_blank" class="text-blue-600 hover:underline">{{ __('quotation.form.open') }}</a>
                                @if($bookedQuotationIds->has($q->id))
                                    <a href="{{ route('admin.package-bookings.show', ['packageBooking' => $bookedQuotationIds[$q->id]]) }}" class="text-success hover:underline">{{ __('quotation.form.view_booking') }}</a>
                                @else
                                    <button type="button" wire:click="openConvertForm({{ $q->id }})" class="text-blue-600 hover:underline">{{ __('quotation.form.make_booking') }}</button>
                                @endif
                            </span>
                        </div>

                        @if($convertingQuotationId === $q->id)
                            <form wire:submit="convertToBooking" class="mt-2 flex flex-wrap items-end gap-2 rounded border border-neutral-200 bg-neutral-50 p-2">
                                <div>
                                    <label class="mb-1 block text-[10px] font-medium text-neutral-600">{{ __('quotation.form.departure_date') }}</label>
                                    <input type="date" wire:model="convert_departure_date" class="rounded border border-neutral-300 px-2 py-1 text-xs">
                                    @error('convert_departure_date') <p class="text-[10px] text-danger">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-medium text-neutral-600">{{ __('quotation.form.return_date') }}</label>
                                    <input type="date" wire:model="convert_return_date" class="rounded border border-neutral-300 px-2 py-1 text-xs">
                                    @error('convert_return_date') <p class="text-[10px] text-danger">{{ $message }}</p> @enderror
                                </div>
                                <x-ui.button type="submit" variant="primary" class="text-xs">{{ __('quotation.form.confirm_booking') }}</x-ui.button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
