<?php

namespace Tests\Feature;

use App\Domain\Booking\Models\BookingStatusHistory;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Livewire\Admin\Desk\AdminDesk;
use App\Livewire\Admin\Desk\CsDesk;
use App\Livewire\Admin\UserManagement\RoleMatrix;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class AdminDeskTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function admin(): User
    {
        return $this->as('admin.bali@indogate.com');
    }

    private function otherBranch(): Branch
    {
        return Branch::where('code', '!=', 'BALI')->firstOrFail();
    }

    private function booking(Branch $branch, BookingStatus $status, $departure, ?User $creator = null): PackageBooking
    {
        $quotation = Quotation::create([
            'branch_id' => $branch->id, 'lead_id' => $this->leadFor($branch)->id,
            'package_id' => $this->packageWithOneHotelRoom($branch)->id,
            'token' => Str::random(48), 'currency' => 'IDR', 'locked_rate' => '1.00000000',
            'valid_until' => now()->addDay(), 'status' => 'sent',
        ]);

        return PackageBooking::create([
            'branch_id' => $branch->id, 'quotation_id' => $quotation->id, 'created_by' => $creator?->id,
            'code' => 'BK-'.strtoupper(Str::random(6)), 'status' => $status,
            'departure_date' => $departure, 'total_minor' => 5_000_000, 'currency' => 'IDR',
        ]);
    }

    public function test_role_access_matrix(): void
    {
        $cs = $this->as('cs.bali@indogate.com');
        $finance = $this->as('finance.bali@indogate.com');
        $super = $this->as('admin@indogate.com');

        $this->actingAs($cs)->get('/id/admin/desk')->assertOk();
        $this->actingAs($cs)->get('/id/admin/control')->assertForbidden();

        $this->actingAs($this->admin())->get('/id/admin/control')->assertOk();
        $this->actingAs($this->admin())->get('/id/admin/desk')->assertForbidden();

        $this->actingAs($finance)->get('/id/admin/desk')->assertForbidden();
        $this->actingAs($finance)->get('/id/admin/control')->assertForbidden();

        $this->actingAs($super)->get('/id/admin/desk')->assertOk();
        $this->actingAs($super)->get('/id/admin/control')->assertOk();
    }

    public function test_admin_is_blocked_from_finance_pricing_and_user_areas(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/id/admin/finance/payments')->assertForbidden();
        $this->actingAs($admin)->get('/id/admin/pricing-rules')->assertForbidden();
        $this->actingAs($admin)->get('/id/admin/roles')->assertForbidden();

        foreach (['payment.verify', 'pricing.manage', 'currency.manage', 'user.manage', 'branch.switch'] as $permission) {
            $this->assertFalse($admin->can($permission), $permission);
        }
        $this->assertTrue($admin->can('report.margin.view'));
    }

    public function test_dashboard_redirects_per_role(): void
    {
        $this->actingAs($this->as('cs.bali@indogate.com'))->get('/id/admin/dashboard')->assertRedirect(route('admin.desk', ['locale' => 'id']));
        $this->actingAs($this->admin())->get('/id/admin/dashboard')->assertRedirect(route('admin.control', ['locale' => 'id']));
        $this->actingAs($this->as('admin@indogate.com'))->get('/id/admin/dashboard')->assertOk();
    }

    public function test_sidebar_links_follow_desk_permissions(): void
    {
        $this->actingAs($this->admin())->get('/id/admin/control')
            ->assertSee(route('admin.control', ['locale' => 'id']))
            ->assertDontSee(route('admin.desk', ['locale' => 'id']));

        $this->actingAs($this->as('admin@indogate.com'))->get('/id/admin/control')
            ->assertDontSee('href="'.route('admin.desk', ['locale' => 'id']).'"', false);

        $this->actingAs($this->as('cs.bali@indogate.com'))->get('/id/admin/desk')
            ->assertDontSee('href="'.route('admin.control', ['locale' => 'id']).'"', false);
    }

    public function test_components_authorize_directly(): void
    {
        Livewire::actingAs($this->as('cs.bali@indogate.com'))->test(AdminDesk::class)->assertForbidden();
        Livewire::actingAs($this->admin())->test(CsDesk::class)->assertForbidden();
    }

    public function test_operations_panels_show_only_this_branch(): void
    {
        $bali = $this->baliBranch();
        $jkt = $this->otherBranch();

        $noDriver = $this->booking($bali, BookingStatus::CONFIRMED, today()->addDays(3));
        $farAway = $this->booking($bali, BookingStatus::CONFIRMED, today()->addDays(20));
        $foreign = $this->booking($jkt, BookingStatus::CONFIRMED, today()->addDays(3));

        Livewire::actingAs($this->admin())->test(AdminDesk::class)
            ->assertSee($noDriver->code)
            ->assertDontSee($farAway->code)
            ->assertDontSee($foreign->code);
    }

    public function test_cancellations_show_reason_and_actor_for_own_branch_only(): void
    {
        $bali = $this->baliBranch();
        $mine = $this->booking($bali, BookingStatus::CANCELLED, today()->addDays(30));
        $theirs = $this->booking($this->otherBranch(), BookingStatus::CANCELLED, today()->addDays(30));

        foreach ([[$mine, 'Customer changed plans'], [$theirs, 'Foreign reason']] as [$booking, $reason]) {
            BookingStatusHistory::create([
                'booking_id' => $booking->id, 'from_status' => BookingStatus::CONFIRMED, 'to_status' => BookingStatus::CANCELLED,
                'user_id' => $this->as('cs.bali@indogate.com')->id, 'reason' => $reason,
            ]);
        }

        Livewire::actingAs($this->admin())->test(AdminDesk::class)
            ->assertSee('Customer changed plans')
            ->assertSee('CS Bali')
            ->assertDontSee('Foreign reason');
    }

    public function test_team_table_counts_only_own_branch_cs(): void
    {
        $bali = $this->baliBranch();
        $cs = $this->as('cs.bali@indogate.com');
        Lead::create(['branch_id' => $bali->id, 'name' => 'Mine', 'phone' => '1', 'country' => 'SA', 'locale' => 'id', 'source' => 'manual', 'status' => 'contacted', 'assigned_to' => $cs->id, 'follow_up_at' => now()->subDay()]);

        $foreignCs = User::factory()->create(['branch_id' => $this->otherBranch()->id, 'name' => 'Jakarta Agent']);
        $foreignCs->assignRole('CS Admin');

        Livewire::actingAs($this->admin())->test(AdminDesk::class)
            ->assertSee('CS Bali')
            ->assertDontSee('Jakarta Agent');
    }

    public function test_team_and_assign_list_exclude_super_admin_and_admin(): void
    {
        $super = User::where('email', 'admin@indogate.com')->firstOrFail();
        $super->update(['branch_id' => $this->baliBranch()->id, 'name' => 'Zed Super']);
        $lead = $this->leadFor($this->baliBranch());

        Livewire::actingAs($this->admin())->test(AdminDesk::class)
            ->assertSee('CS Bali')
            ->assertDontSee('Zed Super')
            ->set('selectedLeads', [$lead->id])
            ->set('assignTo', $super->id)
            ->call('assign')
            ->assertHasErrors('assignTo');
    }

    public function test_bulk_assign_updates_unassigned_leads_and_logs_each_change(): void
    {
        $bali = $this->baliBranch();
        $cs = $this->as('cs.bali@indogate.com');
        $a = $this->leadFor($bali);
        $b = $this->leadFor($bali);
        $foreign = $this->leadFor($this->otherBranch());

        Livewire::actingAs($this->admin())->test(AdminDesk::class)
            ->set('selectedLeads', [$a->id, $b->id, $foreign->id])
            ->set('assignTo', $cs->id)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertSame($cs->id, $a->fresh()->assigned_to);
        $this->assertSame($cs->id, $b->fresh()->assigned_to);
        $this->assertNull($foreign->withoutGlobalScopes()->find($foreign->id)->assigned_to);
        $this->assertSame(2, \Spatie\Activitylog\Models\Activity::where('subject_type', Lead::class)->where('event', 'updated')->count());
    }

    public function test_bulk_assign_rejects_cs_from_other_branch_and_non_lead_users(): void
    {
        $lead = $this->leadFor($this->baliBranch());
        $foreignCs = User::factory()->create(['branch_id' => $this->otherBranch()->id]);
        $foreignCs->assignRole('CS Admin');

        foreach ([$foreignCs->id, $this->as('finance.bali@indogate.com')->id] as $target) {
            Livewire::actingAs($this->admin())->test(AdminDesk::class)
                ->set('selectedLeads', [$lead->id])
                ->set('assignTo', $target)
                ->call('assign')
                ->assertHasErrors('assignTo');
        }

        $this->assertNull($lead->fresh()->assigned_to);
    }

    public function test_seeder_is_idempotent_and_role_stays_segregated(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(1, Role::where('name', 'Admin')->count());
        $this->assertSame(1, Permission::where('name', 'desk.cs')->count());
        $this->assertSame(1, User::where('email', 'admin.bali@indogate.com')->count());
        $this->assertTrue(Role::findByName('CS Admin')->hasPermissionTo('desk.cs'));
        $this->assertFalse(Role::findByName('Admin')->hasPermissionTo('payment.verify'));
        $this->assertFalse(Role::findByName('Admin')->hasPermissionTo('desk.cs'));
    }

    public function test_role_matrix_still_rejects_booking_plus_verify_for_admin_role(): void
    {
        $admin = Role::findByName('Admin');

        Livewire::actingAs($this->as('admin@indogate.com'))->test(RoleMatrix::class)
            ->set('grants.'.$admin->id.'.'.Permission::where('name', 'payment.verify')->value('id'), true)
            ->call('save')
            ->assertHasErrors('grants');

        $this->assertFalse($admin->fresh()->hasPermissionTo('payment.verify'));
    }
}
