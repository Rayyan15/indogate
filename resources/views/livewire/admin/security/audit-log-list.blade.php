<div class="space-y-6">
    <x-ui.page-header
        :title="__('security.audit_logs')"
        :lede="__('security.audit_logs_lede')"
    />

    <!-- Filter Bar -->
    <div class="bg-neutral-0 rounded border border-neutral-200 p-4 shadow-sm space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Channel / Log Name -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1">{{ __('security.filter_log_name') }}</label>
                <select wire:model.live="logName" class="w-full text-sm rounded border-neutral-300 focus:border-red-600 focus:ring-red-600">
                    <option value="">{{ __('security.filter_all_modules') }}</option>
                    @foreach($availableLogNames as $name)
                        <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Actor / Causer -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1">{{ __('security.filter_causer') }}</label>
                <select wire:model.live="causerId" class="w-full text-sm rounded border-neutral-300 focus:border-red-600 focus:ring-red-600">
                    <option value="">{{ __('security.filter_all_actors') }}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1">{{ __('security.date_from') }}</label>
                <input type="date" wire:model.live="dateFrom" class="w-full text-sm rounded border-neutral-300 focus:border-red-600 focus:ring-red-600">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1">{{ __('security.date_to') }}</label>
                <input type="date" wire:model.live="dateTo" class="w-full text-sm rounded border-neutral-300 focus:border-red-600 focus:ring-red-600">
            </div>

            <!-- Search -->
            <div>
                <label class="block text-xs font-semibold text-neutral-700 uppercase tracking-wider mb-1">{{ __('security.details') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('security.search_placeholder') }}" class="w-full text-sm rounded border-neutral-300 focus:border-red-600 focus:ring-red-600">
            </div>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-neutral-0 rounded border border-neutral-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-center text-sm">
                <thead class="bg-neutral-50 text-xs uppercase tracking-wider text-neutral-600 border-b border-neutral-200">
                    <tr>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.time') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.channel') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.actor') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.description') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.subject') }}</th>
                        <th class="px-5 py-3 text-center font-semibold">{{ __('security.details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200">
                    @forelse($logs as $log)
                        @php
                            $channelColor = match($log->log_name) {
                                'auth' => 'bg-sky-50 text-sky-700 border-sky-200',
                                'finance' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'fleet' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'security' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'pricing' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                            };
                            $ip = $log->properties['ip'] ?? null;
                        @endphp
                        <tr class="hover:bg-neutral-50/70 transition-colors">
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs text-neutral-600">
                                <div>{{ $log->created_at->format('Y-m-d H:i:s') }}</div>
                                <div class="text-[11px] text-neutral-400">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $channelColor }}">
                                    {{ ucfirst($log->log_name ?: 'system') }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($log->causer)
                                    <div class="font-medium text-neutral-900">{{ $log->causer->name }}</div>
                                    <div class="text-xs text-neutral-500 font-mono">{{ $log->causer->email }}</div>
                                @else
                                    <span class="text-xs text-neutral-500 italic">{{ __('security.system') }}</span>
                                    @if($ip)
                                        <div class="text-[11px] text-neutral-400 font-mono">IP: {{ $ip }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-neutral-900 font-medium">
                                {{ $log->description }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-neutral-600">
                                @if($log->subject_type)
                                    <div class="font-mono text-[11px] text-neutral-500">{{ class_basename($log->subject_type) }}</div>
                                    <div class="font-semibold text-neutral-700">#{{ $log->subject_id }}</div>
                                @else
                                    <span class="text-neutral-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                @if(!empty($log->properties) && count($log->properties) > 0)
                                    <button
                                        type="button"
                                        wire:click="viewDetails({{ $log->id }})"
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-neutral-300 text-neutral-700 hover:bg-neutral-100 transition-colors"
                                    >
                                        {{ __('security.details') }}
                                    </button>
                                @else
                                    <span class="text-xs text-neutral-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-neutral-500">
                                {{ __('security.empty_logs') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-neutral-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Metadata Details Modal -->
    @if($selectedLogId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-900/50 p-4" x-data @keydown.escape.window="$wire.closeDetails()">
            <div class="w-full max-w-xl rounded-lg bg-neutral-0 shadow-xl border border-neutral-200 overflow-hidden">
                <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4 bg-neutral-50">
                    <div>
                        <h3 class="font-semibold text-neutral-900">{{ __('security.modal_details_title') }} #{{ $selectedLogId }}</h3>
                        <p class="text-xs text-neutral-500 mt-0.5">{{ $selectedDescription }}</p>
                    </div>
                    <button type="button" wire:click="closeDetails" class="text-neutral-400 hover:text-neutral-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-5 max-h-[70vh] overflow-y-auto">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">{{ __('security.properties') }}</h4>
                    <pre class="rounded bg-neutral-900 p-4 font-mono text-xs text-neutral-100 overflow-x-auto whitespace-pre-wrap">{{ json_encode($selectedProperties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div class="border-t border-neutral-200 px-5 py-3 text-end bg-neutral-50">
                    <button type="button" wire:click="closeDetails" class="px-4 py-2 rounded text-sm font-medium border border-neutral-300 text-neutral-700 hover:bg-neutral-100">
                        {{ __('security.close') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
