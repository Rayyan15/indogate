<?php

namespace Tests\Feature;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use App\Livewire\Admin\Desk\CsDesk;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class CsDeskTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    private function cs(): User
    {
        return User::role('CS Admin')->firstOrFail();
    }

    private function bookingFor(Branch $branch, Lead $lead, BookingStatus $status, $departure): PackageBooking
    {
        $quotation = Quotation::create([
            'branch_id' => $branch->id, 'lead_id' => $lead->id,
            'package_id' => $this->packageWithOneHotelRoom($branch)->id,
            'token' => Str::random(48), 'currency' => 'IDR', 'locked_rate' => '1.00000000',
            'valid_until' => now()->addDay(), 'status' => 'sent',
        ]);

        return PackageBooking::create([
            'branch_id' => $branch->id, 'quotation_id' => $quotation->id,
            'code' => 'BK-'.strtoupper(Str::random(6)), 'status' => $status,
            'departure_date' => $departure, 'total_minor' => 5_000_000, 'currency' => 'IDR',
        ]);
    }

    public function test_cs_is_sent_from_dashboard_to_desk(): void
    {
        $this->seed();

        $this->actingAs($this->cs())->get('/id/admin/dashboard')->assertRedirect(route('admin.desk', ['locale' => 'id']));
        $this->actingAs($this->cs())->get('/id/admin/desk')->assertOk()->assertSee(__('desk.follow_up_title', [], 'id'));
    }

    public function test_finance_cannot_open_desk(): void
    {
        $this->seed();

        $this->actingAs(User::role('Finance Admin')->firstOrFail())->get('/id/admin/desk')->assertForbidden();
    }

    public function test_queues_show_leads_quotations_and_bookings(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $booking = $this->bookingFor($branch, $lead, BookingStatus::CONFIRMED, today()->addDay());
        $paidOff = $this->bookingFor($branch, $lead, BookingStatus::CONFIRMED, today()->addDays(9));
        $paidOff->update(['total_minor' => 0]);
        // Open draft quotation (nothing sets SENT yet) must show; converted ones must not.
        $open = Quotation::create([
            'branch_id' => $branch->id, 'lead_id' => $lead->id, 'package_id' => $booking->quotation->package_id,
            'token' => Str::random(48), 'currency' => 'IDR', 'locked_rate' => '1.00000000',
            'valid_until' => now()->addDays(3), 'status' => 'draft',
        ]);

        Livewire::actingAs($this->cs())->test(CsDesk::class)
            ->assertViewHas('leads', fn ($l) => $l['count'] === 1)
            ->assertViewHas('quotations', fn ($q) => $q['count'] === 1 && $q['items']->first()->is($open))
            ->assertViewHas('awaitingPayment', fn ($b) => ! $b['items']->contains($paidOff))
            ->assertViewHas('awaitingPayment', fn ($b) => $b['items']->contains($booking))
            ->assertViewHas('departing', fn ($b) => $b['items']->contains($booking))
            ->assertSee($booking->code)
            ->assertSee('wa.me/966500000000');
    }

    public function test_search_finds_lead_by_phone_and_hides_other_branch(): void
    {
        $this->seed();
        $bali = $this->baliBranch();
        $other = Branch::where('id', '!=', $bali->id)->firstOrFail();

        $this->leadFor($bali);
        Lead::create([
            'branch_id' => $other->id, 'name' => 'Jakarta Guest', 'phone' => '+966511111111',
            'locale' => 'id', 'source' => 'manual', 'status' => 'new',
        ]);

        Livewire::actingAs($this->cs())->test(CsDesk::class)
            ->set('search', '9665')
            ->assertSee('Ahmad')
            ->assertDontSee('Jakarta Guest')
            ->assertViewHas('leads', fn ($l) => $l['count'] === 1);
    }
}
