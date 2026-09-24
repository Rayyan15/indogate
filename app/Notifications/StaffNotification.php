<?php

namespace App\Notifications;

use App\Support\Notifications\NotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Base for admin-panel notifications. Text is rendered at display time from
 * title_key/body_key + params in the viewer's locale, never stored as prose.
 */
abstract class StaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE = '';

    public const SEVERITY = 'info';

    /** @param array<string, mixed> $params */
    public function __construct(
        public array $params,
        public string $url,
        public ?int $branchId,
    ) {}

    public function via(object $notifiable): array
    {
        return NotificationPreferences::for($notifiable)->allowsPush(static::TYPE, now()) ? ['database', WebPushChannel::class] : ['database'];
    }

    /** Lock-screen safe: only the same short text the bell shows, plus the link. */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        $locale = $notifiable instanceof HasLocalePreference ? $notifiable->preferredLocale() : config('app.locale');

        return (new WebPushMessage)
            ->title(__('notifications.'.static::TYPE.'.title', [], $locale))
            ->body(__('notifications.'.static::TYPE.'.body', $this->textParams(), $locale))
            ->icon('/icons/icon.svg')
            ->tag(static::TYPE)
            ->data(['url' => $this->url]);
    }

    /** @return array<string, string> flat string replacements; nested params become dotted keys */
    private function textParams(): array
    {
        return array_map('strval', Arr::dot($this->params));
    }

    public function databaseType(object $notifiable): string
    {
        return static::TYPE;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => static::TYPE,
            'title_key' => 'notifications.'.static::TYPE.'.title',
            'body_key' => 'notifications.'.static::TYPE.'.body',
            'params' => $this->textParams(),
            'url' => $this->url,
            'branch_id' => $this->branchId,
            'severity' => static::SEVERITY,
        ];
    }
}
