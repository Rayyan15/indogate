<?php

namespace App\Support\Notifications;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * A staff member's push settings (push-notification-plan §4.3): per-type push
 * on/off and quiet hours in the branch's timezone. In-app (database)
 * delivery is never affected; quiet hours suppress push, they do not defer it.
 */
class NotificationPreferences
{
    public const DEFAULT_QUIET_FROM = '22:00';

    public const DEFAULT_QUIET_TO = '07:00';

    private function __construct(private readonly User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function pushEnabled(string $type): bool
    {
        return (bool) ($this->user->notification_preferences['push'][$type] ?? true);
    }

    public function quietEnabled(): bool
    {
        return (bool) ($this->user->notification_preferences['quiet']['enabled'] ?? true);
    }

    public function quietFrom(): string
    {
        return $this->user->notification_preferences['quiet']['from'] ?? self::DEFAULT_QUIET_FROM;
    }

    public function quietTo(): string
    {
        return $this->user->notification_preferences['quiet']['to'] ?? self::DEFAULT_QUIET_TO;
    }

    public function isQuietAt(CarbonInterface $moment): bool
    {
        if (! $this->quietEnabled()) {
            return false;
        }

        $now = $moment->copy()->setTimezone($this->user->branch?->timezone ?? config('app.timezone'))->format('H:i');
        [$from, $to] = [$this->quietFrom(), $this->quietTo()];

        // A window like 22:00-07:00 wraps past midnight.
        return $from <= $to ? ($now >= $from && $now < $to) : ($now >= $from || $now < $to);
    }

    public function allowsPush(string $type, CarbonInterface $now): bool
    {
        return $this->pushEnabled($type) && ! $this->isQuietAt($now);
    }
}
