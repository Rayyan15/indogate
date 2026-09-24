<?php

namespace Tests\Feature;

use App\Livewire\Admin\NotificationBell;
use App\Livewire\Admin\Notifications\NotificationIndex;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function notify(User $user, string $name, string $url, string $type = 'new_website_lead'): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'Tests\\Fake\\'.$type,
            'data' => [
                'type' => $type,
                'title_key' => "notifications.$type.title",
                'body_key' => "notifications.$type.body",
                'params' => ['name' => $name],
                'url' => $url,
                'branch_id' => null,
                'severity' => 'info',
            ],
        ]);
    }

    public function test_bell_shows_only_own_notifications_and_unread_count(): void
    {
        $cs = User::role('CS Admin')->firstOrFail();
        $other = User::role('Finance Admin')->firstOrFail();
        $this->notify($cs, 'Mine', url('/id/admin/desk'));
        $this->notify($cs, 'Mine2', url('/id/admin/desk'))->markAsRead();
        $foreign = $this->notify($other, 'Foreign', url('/id/admin/desk'));

        Livewire::actingAs($cs)->test(NotificationBell::class)
            ->assertViewHas('unreadCount', 1)
            ->assertViewHas('notifications', fn ($list) => $list->count() === 2
                && ! $list->contains('id', $foreign->id));
    }

    public function test_open_marks_read_and_redirects_same_host_only(): void
    {
        $cs = User::role('CS Admin')->firstOrFail();
        $local = $this->notify($cs, 'A', url('/id/admin/desk'));
        $evil = $this->notify($cs, 'B', 'https://evil.example.com/phish');

        Livewire::actingAs($cs)->test(NotificationBell::class)
            ->call('open', $local->id)->assertRedirect(url('/id/admin/desk'));
        $this->assertNotNull($local->fresh()->read_at);

        Livewire::actingAs($cs)->test(NotificationBell::class)
            ->call('open', $evil->id)->assertRedirect(route('admin.notifications.index'));
    }

    public function test_safe_url_accepts_relative_paths_only(): void
    {
        $this->assertSame('/en/admin/leads/1/edit', NotificationBell::safeUrl('/en/admin/leads/1/edit'));
        $this->assertNull(NotificationBell::safeUrl('//evil.example.com/x'));
        $this->assertNull(NotificationBell::safeUrl('/\\evil.example.com'));
        $this->assertNull(NotificationBell::safeUrl('https://evil.example.com/x'));
    }

    public function test_foreign_notification_cannot_be_marked(): void
    {
        $cs = User::role('CS Admin')->firstOrFail();
        $foreign = $this->notify(User::role('Finance Admin')->firstOrFail(), 'X', url('/'));

        foreach ([[NotificationBell::class, 'open'], [NotificationIndex::class, 'markRead']] as [$component, $method]) {
            try {
                Livewire::actingAs($cs)->test($component)->call($method, $foreign->id);
                $this->fail("$component::$method accepted a foreign notification id");
            } catch (ModelNotFoundException) {
                // expected: scoped to the user's own notifications
            }
        }
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_mark_all_read_only_touches_own(): void
    {
        $cs = User::role('CS Admin')->firstOrFail();
        $foreign = $this->notify(User::role('Finance Admin')->firstOrFail(), 'X', url('/'));
        $this->notify($cs, 'A', url('/'));

        Livewire::actingAs($cs)->test(NotificationBell::class)->call('markAllRead')->assertViewHas('unreadCount', 0);
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_history_page_renders_and_filters(): void
    {
        $cs = User::role('CS Admin')->firstOrFail();
        $this->notify($cs, 'A', url('/'), 'new_website_lead');
        $this->notify($cs, 'B', url('/'), 'quotation_expiring');

        $this->actingAs($cs)->get('/id/admin/notifications')->assertOk();

        Livewire::actingAs($cs)->test(NotificationIndex::class)
            ->set('type', 'quotation_expiring')
            ->assertViewHas('notifications', fn ($p) => $p->total() === 1);
    }
}
