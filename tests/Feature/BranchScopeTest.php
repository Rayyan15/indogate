<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Hotel;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_does_not_see_other_branch_records_in_list(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jakarta = Branch::where('code', 'JKT')->firstOrFail();

        Hotel::create(['branch_id' => $bali->id, 'name' => ['id' => 'Hotel Bali'], 'base_price' => 100]);
        Hotel::create(['branch_id' => $jakarta->id, 'name' => ['id' => 'Hotel Jakarta'], 'base_price' => 100]);

        $csBali = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $this->actingAs($csBali);

        $this->assertSame(1, Hotel::count());
        $this->assertSame('Hotel Bali', Hotel::first()->name);
    }

    public function test_super_admin_switching_branch_changes_visible_data(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jakarta = Branch::where('code', 'JKT')->firstOrFail();

        Hotel::create(['branch_id' => $bali->id, 'name' => ['id' => 'Hotel Bali'], 'base_price' => 100]);
        Hotel::create(['branch_id' => $jakarta->id, 'name' => ['id' => 'Hotel Jakarta'], 'base_price' => 100]);

        $superAdmin = User::role('Super Admin')->firstOrFail();
        $this->actingAs($superAdmin);

        $this->assertSame('Hotel Bali', Hotel::first()->name);

        CurrentBranch::switchTo($jakarta->id);

        $this->assertSame('Hotel Jakarta', Hotel::first()->name);
    }

    public function test_direct_url_to_cross_branch_booking_returns_403_not_404(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jakarta = Branch::where('code', 'JKT')->firstOrFail();

        $customer = Customer::create(['user_id' => User::factory()->create()->id, 'full_name' => 'Guest']);

        $jakartaBooking = Booking::create([
            'branch_id' => $jakarta->id,
            'booking_number' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'status' => 'confirmed',
            'total_amount' => 1000,
            'currency' => 'IDR',
        ]);

        $csBali = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $response = $this->actingAs($csBali)->get(route('admin.bookings.show', $jakartaBooking));

        $response->assertForbidden();
    }
}
