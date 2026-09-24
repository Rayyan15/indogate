<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for admin-panel notifications. Text is rendered at display time from
 * title_key/body_key + params in the viewer's locale, never stored as prose.
 */
abstract class StaffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE = '';

    public const SEVERITY = 'info';

    /** @param array<string, string> $params */
    public function __construct(
        public array $params,
        public string $url,
        public ?int $branchId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
            'params' => array_map('strval', $this->params),
            'url' => $this->url,
            'branch_id' => $this->branchId,
            'severity' => static::SEVERITY,
        ];
    }
}
