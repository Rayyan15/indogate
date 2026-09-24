<?php

namespace Tests\Feature\Reporting;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Lead\Models\Lead;
use App\Domain\Reporting\Services\ReportExportService;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PaymentChannel;
use App\Livewire\Admin\Dashboard\DashboardOverview;
use App\Livewire\Admin\Reporting\ReportsCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_sales_margin_export_generates_valid_csv(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 5_000_000,
            currency: 'IDR',
            type: Payment::TYPE_DOWN_PAYMENT,
            channel: PaymentChannel::BANK_TRANSFER->value,
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        $exportService = new ReportExportService;
        $csv = $exportService->exportSalesMargin(collect([$booking]));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Kode Pemesanan', $csv);
        $this->assertStringContainsString('Pendapatan Kotor (IDR)', $csv);
        $this->assertStringContainsString('Margin Sesungguhnya (IDR)', $csv);
        $this->assertStringContainsString($booking->code, $csv);

        // Test Livewire streamed export
        Livewire::actingAs($financeUser)
            ->test(DashboardOverview::class)
            ->call('export', 'sales')
            ->assertFileDownloaded();
    }

    public function test_lead_conversion_export_generates_valid_csv(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $csUser = User::role('CS Admin')->firstOrFail();

        $lead = Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Calon Wisatawan Timur Tengah',
            'phone' => '+966500000000',
            'status' => LeadStatus::CONTACTED,
            'source' => LeadSource::WEBSITE,
        ]);

        $exportService = new ReportExportService;
        $csv = $exportService->exportLeadConversion(collect([$lead]));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('ID Prospek', $csv);
        $this->assertStringContainsString('Nama Tamu', $csv);
        $this->assertStringContainsString('Calon Wisatawan Timur Tengah', $csv);

        Livewire::actingAs($csUser)
            ->test(ReportsCenter::class)
            ->set('activeTab', 'lead_conversion')
            ->call('export')
            ->assertFileDownloaded();
    }

    public function test_streamed_lead_csv_matches_string_export_and_guards_formulas(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $csUser = User::role('CS Admin')->firstOrFail();

        Lead::create([
            'branch_id' => $branch->id,
            'name' => '=HYPERLINK("http://evil")',
            'phone' => '+966500000001',
            'status' => LeadStatus::CONTACTED,
            'source' => LeadSource::WEBSITE,
        ]);

        $service = new ReportExportService;
        $expected = $service->exportLeadConversion(Lead::withoutGlobalScopes()->lazyById(500));

        ob_start();
        $service->streamLeadConversion(Lead::withoutGlobalScopes()->lazyById(500));
        $streamed = ob_get_clean();

        $this->assertSame($expected, $streamed);
        $this->assertStringContainsString("'=HYPERLINK", $streamed);

        $response = Livewire::actingAs($csUser)
            ->test(ReportsCenter::class)
            ->set('activeTab', 'lead_conversion')
            ->call('export')
            ->assertFileDownloaded();
    }

    public function test_unauthorized_user_cannot_export_sales_margin(): void
    {
        $this->seed();
        $csRole = Role::findByName('CS Admin');
        $csRole->revokePermissionTo('report.margin.view');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $csUser = User::role('CS Admin')->firstOrFail();

        Livewire::actingAs($csUser)
            ->test(DashboardOverview::class)
            ->call('export', 'sales')
            ->assertForbidden();
    }
}
