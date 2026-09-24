<?php

namespace App\Livewire\Admin\Notifications;

use App\Livewire\Admin\NotificationBell;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationIndex extends Component
{
    use WithPagination;

    public const TYPES = [
        'new_website_lead',
        'online_payment_received',
        'online_payment_failed',
        'manual_payment_pending',
        'storefront_proof_uploaded',
        'quotation_expiring',
        'departure_without_driver',
        'lead_follow_up_due',
    ];

    #[Url]
    public string $type = '';

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
    }

    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($url = NotificationBell::safeUrl($notification->data['url'] ?? null)) {
            $this->redirect($url);
        }
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render(): View
    {
        $query = auth()->user()->notifications()->latest();

        if (in_array($this->type, self::TYPES, true)) {
            $query->where('data->type', $this->type);
        }

        return view('livewire.admin.notifications.notification-index', [
            'notifications' => $query->paginate(20),
            'typeOptions' => collect(self::TYPES)->mapWithKeys(fn ($t) => [$t => __("notifications.$t.title")])->all(),
        ]);
    }
}
