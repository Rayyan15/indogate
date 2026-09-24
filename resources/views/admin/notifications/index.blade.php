<x-admin-layout>
    <x-slot name="header">{{ __('notifications.history_title') }}</x-slot>

    <script src="{{ asset('js/staff-push.js') }}"></script>
    <livewire:admin.notifications.notification-preferences-form />
    <livewire:admin.notifications.notification-index />
</x-admin-layout>
