<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationBell extends Component
{
    /** Mark one of the user's own notifications read, then follow its link (same host only). */
    public function open(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $this->redirect(self::safeUrl($notification->data['url'] ?? null) ?? route('admin.notifications.index'));
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    /** Only allow redirects back into this application (prevents open redirect). */
    public static function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host === request()->getHost() ? $url : null;
    }

    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.admin.notification-bell', [
            'unreadCount' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(10)->get(),
        ]);
    }
}
