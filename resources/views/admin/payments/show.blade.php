<x-admin-layout>
    <x-slot name="header">{{ __('admin.common.review') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.payments.eyebrow')" :title="'#' . $payment->id" :lede="__('admin.payments.submitted_ago') . ' ' . $payment->created_at->diffForHumans()" />

    <x-ui.button variant="ghost" :href="route('admin.payments.index')" class="mb-6">{{ __('admin.payments.back_to_payments') }}</x-ui.button>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.panel :title="__('admin.payments.details')">
            <dl class="divide-y divide-neutral-100">
                <div class="flex justify-between py-3">
                    <dt class="text-xs text-neutral-500">{{ __('admin.payments.customer') }}</dt>
                    <dd class="text-sm font-medium text-neutral-900">{{ $payment->booking->customer->user->name ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-xs text-neutral-500">{{ __('admin.payments.booking_ref') }}</dt>
                    <dd class="font-mono text-sm text-neutral-700">{{ substr($payment->booking->booking_number ?? '', 0, 10) }}</dd>
                </div>
                <div class="flex justify-between py-3">
                    <dt class="text-xs text-neutral-500">{{ __('admin.payments.amount') }}</dt>
                    <dd class="font-mono text-lg font-semibold text-neutral-900">IDR {{ number_format($payment->amount) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-xs text-neutral-500">{{ __('admin.common.status') }}</dt>
                    <dd>
                        <x-ui.status :status="$payment->status === 'verified' ? 'paid' : ($payment->status === 'rejected' ? 'cancelled' : 'pending')">
                            {{ __('admin.common.booking_status.' . $payment->status) }}
                        </x-ui.status>
                    </dd>
                </div>
                @if($payment->verified_at)
                <div class="flex justify-between py-3">
                    <dt class="text-xs text-neutral-500">{{ __('admin.payments.processed_at') }}</dt>
                    <dd class="text-sm text-neutral-700">{{ $payment->verified_at->format('d M Y, H:i') }}</dd>
                </div>
                @endif
            </dl>

            @if($payment->status === 'pending')
            <div class="mt-6 flex gap-3 border-t border-neutral-200 pt-6">
                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" class="flex-1">
                    @csrf
                    <x-ui.button variant="primary" type="submit" class="w-full">{{ __('admin.payments.verify_confirm') }}</x-ui.button>
                </form>
                <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" onsubmit="return confirm('{{ __('admin.payments.reject_confirm') }}')">
                    @csrf
                    <x-ui.button variant="secondary" type="submit">{{ __('admin.payments.reject') }}</x-ui.button>
                </form>
            </div>
            @endif
        </x-ui.panel>

        <x-ui.panel :title="__('admin.payments.proof_title')" flush>
            @if($payment->proof_file)
            <div class="border-t border-neutral-100 p-5 first:border-t-0">
                <div class="mb-3 flex items-center justify-between">
                    <p class="text-xs text-neutral-500">{{ __('admin.payments.submitted_ago') }} {{ $payment->created_at->diffForHumans() }}</p>
                    <x-ui.button variant="ghost" :href="URL::signedRoute('admin.payments.download-proof', ['locale' => app()->getLocale(), 'proof' => $payment->id])" class="text-xs">{{ __('admin.payments.open_full') }}</x-ui.button>
                </div>
                <div class="overflow-hidden rounded border border-neutral-200">
                    <img src="{{ URL::signedRoute('admin.payments.download-proof', ['locale' => app()->getLocale(), 'proof' => $payment->id]) }}" alt="Payment Proof" class="max-h-96 w-full object-contain">
                </div>
            </div>
            @else
            <div class="p-5">
                <x-ui.empty :title="__('admin.payments.no_proof_yet')" />
            </div>
            @endif
        </x-ui.panel>
    </div>
</x-admin-layout>
