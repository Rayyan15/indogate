<?php

namespace Tests\Feature;

use App\Livewire\Admin\Notifications\NotificationPreferencesForm;
use App\Models\User;
use App\Notifications\NewWebsiteLead;
use App\Support\Notifications\NotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function staff(): User
    {
        return User::role('CS Admin')->firstOrFail();
    }

    private function payload(): array
    {
        return ['endpoint' => self::ENDPOINT, 'keys' => ['p256dh' => 'pub-key', 'auth' => 'auth-key'], 'contentEncoding' => 'aes128gcm'];
    }

    private function lead(): NewWebsiteLead
    {
        return new NewWebsiteLead(['name' => 'Ahmad'], '/id/admin/desk', null);
    }

    public function test_staff_stores_and_removes_own_subscription(): void
    {
        $user = $this->staff();

        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store', 'id'), $this->payload())->assertCreated();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        // Same device re-subscribing updates instead of duplicating.
        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store', 'id'), $this->payload())->assertCreated();
        $this->assertSame(1, $user->pushSubscriptions()->count());

        $this->actingAs($user)->deleteJson(route('admin.push-subscriptions.destroy', 'id'), ['endpoint' => self::ENDPOINT])->assertNoContent();
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_subscription_endpoints_require_staff_and_valid_payload(): void
    {
        $this->postJson(route('admin.push-subscriptions.store', 'id'), $this->payload())->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.push-subscriptions.store', 'id'), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->staff())
            ->postJson(route('admin.push-subscriptions.store', 'id'), ['endpoint' => 'http://insecure.example.com', 'keys' => []])
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_user_cannot_delete_another_users_subscription(): void
    {
        $owner = $this->staff();
        $other = User::role('Finance Admin')->firstOrFail();
        $owner->updatePushSubscription(self::ENDPOINT, 'pub-key', 'auth-key');

        $this->actingAs($other)->deleteJson(route('admin.push-subscriptions.destroy', 'id'), ['endpoint' => self::ENDPOINT])->assertNoContent();

        $this->assertSame(1, $owner->pushSubscriptions()->count());
    }

    public function test_push_channel_is_used_outside_quiet_hours_and_toggled_per_type(): void
    {
        $user = $this->staff();
        Carbon::setTestNow(Carbon::parse('2026-09-24 12:00', $user->branch->timezone));

        $this->assertSame(['database', WebPushChannel::class], $this->lead()->via($user));

        $user->forceFill(['notification_preferences' => ['push' => [NewWebsiteLead::TYPE => false]]])->save();
        $this->assertSame(['database'], $this->lead()->via($user->fresh()));
    }

    public function test_quiet_hours_suppress_push_but_keep_in_app_using_branch_timezone(): void
    {
        $user = $this->staff();
        $user->branch->update(['timezone' => 'Asia/Jakarta']);
        $prefs = NotificationPreferences::for($user->fresh());

        // The default window is 22:00-07:00 branch time and wraps past midnight.
        $this->assertTrue($prefs->isQuietAt(Carbon::parse('2026-09-24 23:30', 'Asia/Jakarta')));
        $this->assertTrue($prefs->isQuietAt(Carbon::parse('2026-09-24 06:59', 'Asia/Jakarta')));
        $this->assertFalse($prefs->isQuietAt(Carbon::parse('2026-09-24 09:00', 'Asia/Jakarta')));
        // The same instant expressed in UTC gives the same answer.
        $this->assertTrue($prefs->isQuietAt(Carbon::parse('2026-09-24 16:30', 'UTC')));

        Carbon::setTestNow(Carbon::parse('2026-09-24 23:30', 'Asia/Jakarta'));
        $this->assertSame(['database'], $this->lead()->via($user->fresh()));
    }

    public function test_quiet_hours_can_be_disabled_and_customised(): void
    {
        $user = $this->staff();
        $user->branch->update(['timezone' => 'UTC']);

        $user->forceFill(['notification_preferences' => ['quiet' => ['enabled' => false, 'from' => '22:00', 'to' => '07:00']]])->save();
        $this->assertFalse(NotificationPreferences::for($user->fresh())->isQuietAt(Carbon::parse('2026-09-24 23:00', 'UTC')));

        $user->forceFill(['notification_preferences' => ['quiet' => ['enabled' => true, 'from' => '11:00', 'to' => '13:00']]])->save();
        $this->assertTrue(NotificationPreferences::for($user->fresh())->isQuietAt(Carbon::parse('2026-09-24 12:00', 'UTC')));
    }

    public function test_endpoint_must_be_a_known_push_service_on_store_and_destroy(): void
    {
        $user = $this->staff();
        $bad = ['endpoint' => 'https://internal.example.com/hook'] + $this->payload();

        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store', 'id'), $bad)->assertJsonValidationErrors('endpoint');
        $this->actingAs($user)->deleteJson(route('admin.push-subscriptions.destroy', 'id'), ['endpoint' => $bad['endpoint']])->assertJsonValidationErrors('endpoint');

        $apple = ['endpoint' => 'https://web.push.apple.com/abc'] + $this->payload();
        $this->actingAs($user)->postJson(route('admin.push-subscriptions.store', 'id'), $apple)->assertCreated();
    }

    public function test_subscriptions_are_removed_on_logout_and_deactivation(): void
    {
        $user = $this->staff();
        $user->updatePushSubscription(self::ENDPOINT, 'pub-key', 'auth-key');

        $this->actingAs($user)->post(route('logout'));
        $this->assertSame(0, $user->pushSubscriptions()->count());

        $user->updatePushSubscription(self::ENDPOINT, 'pub-key', 'auth-key');
        $user->update(['is_active' => false]);
        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_web_push_flattens_nested_params_and_uses_default_locale(): void
    {
        app()->setLocale('en');
        config(['app.locale' => 'id']);
        $message = (new NewWebsiteLead(['name' => 'Ahmad', 'extra' => ['a' => 1]], '/x', null))->toWebPush($this->staff())->toArray();

        $this->assertSame('Lead baru: Ahmad', $message['body']);
    }
    public function test_web_push_payload_is_minimal_and_translated(): void
    {
        app()->setLocale('id');
        $message = $this->lead()->toWebPush($this->staff())->toArray();

        $this->assertSame('Lead baru dari website', $message['title']);
        $this->assertSame('Lead baru: Ahmad', $message['body']);
        $this->assertSame(['url' => '/id/admin/desk'], $message['data']);
    }

    public function test_preferences_form_saves_own_preferences_and_validates_times(): void
    {
        $user = $this->staff();

        Livewire::actingAs($user)->test(NotificationPreferencesForm::class)
            ->set('push.'.NewWebsiteLead::TYPE, false)
            ->set('quietFrom', '21:30')
            ->set('quietTo', '06:15')
            ->call('save')
            ->assertHasNoErrors();

        $prefs = NotificationPreferences::for($user->fresh());
        $this->assertFalse($prefs->pushEnabled(NewWebsiteLead::TYPE));
        $this->assertTrue($prefs->pushEnabled('online_payment_received'));
        $this->assertSame('21:30', $prefs->quietFrom());
        $this->assertSame('06:15', $prefs->quietTo());

        Livewire::actingAs($user)->test(NotificationPreferencesForm::class)
            ->set('quietFrom', '25:99')
            ->call('save')
            ->assertHasErrors(['quietFrom' => 'date_format']);
    }

    public function test_notifications_page_renders_enable_button_when_vapid_configured(): void
    {
        config(['webpush.vapid.public_key' => 'BPublicKeyForTest']);

        $this->actingAs($this->staff())->get(route('admin.notifications.index', 'id'))
            ->assertOk()
            ->assertSee(__('notifications.push.enable'))
            ->assertSee('rel="manifest"', false);
    }
}
