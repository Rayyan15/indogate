<div>
    <x-ui.page-header :eyebrow="__('lead.eyebrow')" :title="$lead ? __('lead.form.edit_title') : __('lead.form.create_title')" />

    <form wire:submit="save" class="max-w-xl space-y-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.name') }}</label>
            <input type="text" wire:model="name" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.phone') }}</label>
            <input type="text" wire:model="phone" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            @error('phone') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.form.country') }}</label>
                <input type="text" wire:model="country" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.form.locale') }}</label>
                <select wire:model="locale" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    <option value="id">Indonesia</option>
                    <option value="en">English</option>
                    <option value="ar">العربية</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.source') }}</label>
                <select wire:model="source" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach ($sources as $s)
                        <option value="{{ $s->value }}">{{ __('lead.source.'.$s->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.status') }}</label>
                <select wire:model.live="status" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ __('lead.status.'.$s->value) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.assignee') }}</label>
            <select wire:model="assigned_to" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
                <option value="">—</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        @if($status === 'lost')
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.form.lost_reason') }}</label>
                <textarea wire:model="lost_reason" rows="2" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm"></textarea>
                @error('lost_reason') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.form.follow_up') }}</label>
            <input type="datetime-local" wire:model="follow_up_at" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            @error('follow_up_at') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <x-ui.button type="submit" variant="primary">{{ __('lead.form.save') }}</x-ui.button>
    </form>

    @if($lead)
        <div class="mt-8 max-w-xl">
            <h3 class="font-display text-sm font-medium text-neutral-900">{{ __('lead.form.activity_log') }}</h3>
            <ul class="mt-2 space-y-2 text-xs text-neutral-600">
                @forelse($lead->activities as $activity)
                    <li class="border-b border-neutral-100 pb-2">
                        <span class="font-medium">{{ $activity->type }}</span>
                        @if($activity->note) — {{ $activity->note }} @endif
                        <span class="text-neutral-400">· {{ $activity->created_at->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="text-neutral-400">{{ __('lead.form.no_activity') }}</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-8 max-w-xl">
            <livewire:admin.lead.create-quotation :lead="$lead" :key="'quotation-'.$lead->id" />
        </div>
    @endif
</div>
