<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

/**
 * Single entry point for staff notifications (push-notification-plan §4.1):
 * recipients by permission (never role name) + same branch + active, minus
 * the actor; same type+record is sent at most once per 10 minutes.
 */
class Notify
{
    /**
     * @param  string|null  $permission  users holding it in $notification->branchId
     * @param  iterable<User|int|null>  $extra  explicit users (e.g. booking creator)
     */
    public static function send(StaffNotification $notification, int|string $recordId, ?string $permission = null, iterable $extra = []): void
    {
        $recipients = self::recipients($notification->branchId, $permission, $extra);

        if ($recipients->isEmpty() || ! Cache::add('notify:'.$notification::TYPE.':'.$recordId, true, now()->addMinutes(10))) {
            return;
        }

        Notification::send($recipients, $notification);
    }

    /** @return Collection<int, User> */
    public static function recipients(?int $branchId, ?string $permission, iterable $extra = []): Collection
    {
        $ids = collect($extra)->map(fn ($u) => $u instanceof User ? $u->id : $u)->filter();

        // Unseeded permission (fresh installs, some tests) means nobody holds it.
        if ($permission && Permission::where('name', $permission)->exists()) {
            $ids = $ids->merge(User::permission($permission)->where('branch_id', $branchId)->pluck('id'));
        }

        return User::whereIn('id', $ids->unique())
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->when(auth()->id(), fn ($q, $actor) => $q->whereKeyNot($actor))
            ->get();
    }

    /** Absolute admin URL; admin routes sit under the {locale} prefix. */
    public static function url(string $route, array $params = []): string
    {
        // Relative path: queued/CLI senders don't know the host staff browse on.
        return route($route, ['locale' => config('app.locale')] + $params, false);
    }
}
