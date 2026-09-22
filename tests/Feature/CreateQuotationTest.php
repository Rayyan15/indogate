<?php

namespace Tests\Feature;

use App\Domain\Lead\QuotationGenerator;
use App\Enums\PaymentChannel;
use App\Livewire\Admin\Lead\CreateQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class CreateQuotationTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_generator_persists_snapshot_not_a_live_reference(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch, 1_000_000);

        $quotation = (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'IDR',
        );

        $this->assertSame('IDR', $quotation->currency);
        $this->assertSame($lead->id, $quotation->lead_id);
        $this->assertSame($package->id, $quotation->package_id);
        $this->assertNotEmpty($quotation->token);
        $this->assertCount(1, $quotation->items);

        // 1_000_000 cost * 3 nights = 3_000_000, +20% margin = 3_600_000
        $this->assertSame(3_600_000, $quotation->items->first()->total_minor);
    }

    public function test_livewire_component_can_generate_and_convert_to_booking(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch, 1_000_000);

        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $component = Livewire::actingAs($cs)
            ->test(CreateQuotation::class, ['lead' => $lead])
            ->set('package_id', $package->id)
            ->set('pax', 2)
            ->set('preview_date', '2026-06-01')
            ->set('currency', 'IDR')
            ->set('channel', 'bank_transfer')
            ->call('generate')
            ->assertHasNoErrors();

        $quotation = $lead->quotations()->first();
        $this->assertNotNull($quotation);

        $component->call('openConvertForm', $quotation->id)
            ->set('convert_departure_date', '2026-06-01')
            ->set('convert_return_date', '2026-06-05')
            ->call('convertToBooking')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('package_bookings', [
            'quotation_id' => $quotation->id,
            'status' => 'confirmed',
            'departure_date' => '2026-06-01 00:00:00',
        ]);
    }

    public function test_converting_to_booking_requires_booking_manage_permission(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch, 1_000_000);

        // User with lead.manage and quotation permissions, but without booking.manage
        $leadAgent = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $leadAgent->givePermissionTo(['lead.manage']);

        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        // Create quotation by CS Admin
        $component = Livewire::actingAs($cs)
            ->test(CreateQuotation::class, ['lead' => $lead])
            ->set('package_id', $package->id)
            ->set('pax', 2)
            ->set('preview_date', '2026-06-01')
            ->set('currency', 'IDR')
            ->set('channel', 'bank_transfer')
            ->call('generate')
            ->assertHasNoErrors();

        $quotation = $lead->quotations()->first();

        // Attempt to convert to booking by user without booking.manage must throw 403 Forbidden
        Livewire::actingAs($leadAgent)
            ->test(CreateQuotation::class, ['lead' => $lead])
            ->call('openConvertForm', $quotation->id)
            ->set('convert_departure_date', '2026-06-01')
            ->call('convertToBooking')
            ->assertForbidden();
    }
}
