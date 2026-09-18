<x-admin-layout>
    <x-slot name="header">{{ __('booking.eyebrow') }}</x-slot>

    @livewire('admin.booking.package-booking-show', ['packageBooking' => $packageBooking])
</x-admin-layout>
