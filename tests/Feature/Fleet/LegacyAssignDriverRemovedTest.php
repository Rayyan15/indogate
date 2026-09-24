<?php

namespace Tests\Feature\Fleet;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LegacyAssignDriverRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_assign_driver_route_is_gone(): void
    {
        $this->seed();

        $this->assertFalse(Route::has('admin.bookings.assign-driver'));

        $admin = User::where('email', 'admin@indogate.com')->firstOrFail();
        $this->actingAs($admin)->post('/id/admin/bookings/1/assign-driver', ['driver_id' => 1])->assertStatus(405); // path only matches a GET catch-all now
    }
}
