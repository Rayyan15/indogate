<x-admin-layout>
    <x-slot name="header">{{ __('nav.payments') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.payments.eyebrow')" :title="__('admin.payments.index_title')" :lede="__('admin.payments.index_lede')" />

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.payments.payment') }}</x-ui.th>
            <x-ui.th>{{ __('admin.payments.booking_ref') }}</x-ui.th>
            <x-ui.th>{{ __('admin.payments.customer') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.payments.amount') }}</x-ui.th>
            <x-ui.th>{{ __('admin.common.status') }}</x-ui.th>
            <x-ui.th>{{ __('admin.payments.submitted') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($payments as $payment)
        <x-ui.tr>
            <x-ui.td class="font-mono font-semibold text-neutral-900">#{{ $payment->id }}</x-ui.td>
            <x-ui.td class="font-mono text-neutral-500">{{ substr($payment->booking->booking_number ?? '', 0, 8) }}</x-ui.td>
            <x-ui.td>{{ $payment->booking->customer->user->name ?? 'N/A' }}</x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($payment->amount) }}</x-ui.td>
            <x-ui.td>
                <x-ui.status :status="$payment->status === 'verified' ? 'paid' : ($payment->status === 'rejected' ? 'cancelled' : 'pending')">
                    {{ __('admin.common.booking_status.' . $payment->status) }}
                </x-ui.status>
            </x-ui.td>
            <x-ui.td class="font-mono text-neutral-500">{{ $payment->created_at->format('d M Y, H:i') }}</x-ui.td>
            <x-ui.td numeric>
                <x-ui.button variant="ghost" :href="route('admin.payments.show', $payment)">{{ __('admin.common.review') }}</x-ui.button>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="7" class="p-0">
            <x-ui.empty :title="__('admin.payments.all_caught_up')" :text="__('admin.payments.no_pending_text')" />
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $payments->links() }}</div>
</x-admin-layout>
