<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Security\AuditLogList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_audit_log_page(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get(route('admin.security.audit-logs.index'))
            ->assertOk()
            ->assertSeeLivewire(AuditLogList::class);
    }

    public function test_user_without_activitylog_view_is_forbidden(): void
    {
        $this->seed();
        $csUser = User::role('CS Admin')->firstOrFail();

        $this->actingAs($csUser)
            ->get(route('admin.security.audit-logs.index'))
            ->assertForbidden();

        Livewire::actingAs($csUser)
            ->test(AuditLogList::class)
            ->assertForbidden();
    }

    public function test_audit_log_filtering_and_modal_inspection(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();

        activity('auth')
            ->causedBy($superAdmin)
            ->withProperties(['ip' => '127.0.0.1'])
            ->log('Sample user login');

        activity('finance')
            ->causedBy($superAdmin)
            ->withProperties(['amount' => 5000000])
            ->log('Sample payment approval');

        Livewire::actingAs($superAdmin)
            ->test(AuditLogList::class)
            ->assertSee('Sample user login')
            ->assertSee('Sample payment approval')
            ->set('logName', 'auth')
            ->assertSee('Sample user login')
            ->assertDontSee('Sample payment approval')
            ->set('logName', '')
            ->set('search', 'approval')
            ->assertDontSee('Sample user login')
            ->assertSee('Sample payment approval');
    }
}
