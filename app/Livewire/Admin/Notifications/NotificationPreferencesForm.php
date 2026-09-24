<?php

namespace App\Livewire\Admin\Notifications;

use App\Support\Notifications\NotificationPreferences;
use App\Support\Notifications\NotificationTypes;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationPreferencesForm extends Component
{
    /** @var array<string, bool> push on/off per notification type */
    public array $push = [];

    public bool $quietEnabled = true;

    public string $quietFrom = '';

    public string $quietTo = '';

    public bool $saved = false;

    public function mount(): void
    {
        $preferences = NotificationPreferences::for(auth()->user());

        foreach (NotificationTypes::ALL as $type) {
            $this->push[$type] = $preferences->pushEnabled($type);
        }
        $this->quietEnabled = $preferences->quietEnabled();
        $this->quietFrom = $preferences->quietFrom();
        $this->quietTo = $preferences->quietTo();
    }

    public function save(): void
    {
        $this->validate([
            'quietFrom' => ['required', 'date_format:H:i'],
            'quietTo' => ['required', 'date_format:H:i'],
        ]);

        auth()->user()->forceFill(['notification_preferences' => [
            'push' => collect(NotificationTypes::ALL)->mapWithKeys(fn ($type) => [$type => (bool) ($this->push[$type] ?? true)])->all(),
            'quiet' => ['enabled' => $this->quietEnabled, 'from' => $this->quietFrom, 'to' => $this->quietTo],
        ]])->save();

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.admin.notifications.notification-preferences-form', [
            'types' => NotificationTypes::ALL,
            'timezone' => auth()->user()->branch?->timezone ?? config('app.timezone'),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
