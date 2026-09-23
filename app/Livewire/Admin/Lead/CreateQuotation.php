<?php

namespace App\Livewire\Admin\Lead;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Booking\Exceptions\QuotationNotConvertibleException;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Lead\QuotationGenerator;
use App\Domain\Packaging\Models\Package;
use App\Domain\Pricing\Exceptions\CurrencyMismatchException;
use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Enums\PaymentChannel;
use App\Support\Branch\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateQuotation extends Component
{
    /** An id, not the Eloquent model — see LeadForm::$leadId docblock. */
    public int $leadId;

    public ?int $package_id = null;

    public int $pax = 2;

    public string $preview_date = '';

    public string $currency = 'IDR';

    public string $channel = 'bank_transfer';

    public ?string $generatedLink = null;

    /** Which quotation the "convert to booking" mini-form is open for, null = closed. */
    public ?int $convertingQuotationId = null;

    public string $convert_departure_date = '';

    public string $convert_return_date = '';

    public function mount(Lead $lead): void
    {
        $this->authorize('view', $lead);
        $this->leadId = $lead->id;
        $this->preview_date = now()->addDays(30)->toDateString();
    }

    public function generate(QuotationGenerator $generator): void
    {
        $this->authorize('create', Quotation::class);

        $data = $this->validate([
            'package_id' => [
                'required',
                Rule::exists('packages', 'id')->where('branch_id', CurrentBranch::id()),
            ],
            'pax' => ['required', 'integer', 'min:1', 'max:500'],
            'preview_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'channel' => ['required', Rule::enum(PaymentChannel::class)],
        ]);

        $lead = Lead::findOrFail($this->leadId);
        $package = Package::findOrFail($data['package_id']);

        try {
            $quotation = $generator->generate(
                lead: $lead,
                package: $package,
                pax: $data['pax'],
                previewDate: Carbon::parse($data['preview_date']),
                channel: PaymentChannel::from($data['channel']),
                currency: $data['currency'],
            );
        } catch (ExchangeRateNotFoundException|CurrencyMismatchException|\InvalidArgumentException $e) {
            $this->addError('currency', $e->getMessage());

            return;
        }

        $this->generatedLink = route('quotations.public-show', [
            'locale' => $lead->locale,
            'quotation' => $quotation,
        ]);

        $this->dispatch('lead-saved');
    }

    public function openConvertForm(int $quotationId): void
    {
        $this->convertingQuotationId = $quotationId;
        $this->convert_departure_date = now()->addDays(30)->toDateString();
        $this->convert_return_date = '';
    }

    public function convertToBooking(): void
    {
        $quotation = Quotation::findOrFail($this->convertingQuotationId);
        $this->authorize('view', $quotation);
        $this->authorize('create', PackageBooking::class);

        $data = $this->validate([
            'convert_departure_date' => ['required', 'date'],
            'convert_return_date' => ['nullable', 'date', 'after_or_equal:convert_departure_date'],
        ]);

        try {
            $booking = (new ConvertQuotationToBooking)->convert(
                $quotation,
                $data['convert_departure_date'],
                $data['convert_return_date'] ?: null,
                Auth::user(),
            );
        } catch (QuotationNotConvertibleException $e) {
            $this->addError('convert_departure_date', $e->getMessage());

            return;
        }

        $this->convertingQuotationId = null;
        $this->redirect(route('admin.package-bookings.show', ['locale' => app()->getLocale(), 'packageBooking' => $booking]));
    }

    public function render(): View
    {
        $lead = Lead::findOrFail($this->leadId);
        $quotations = $lead->quotations()->with('package')->latest()->get();
        $bookedQuotationIds = PackageBooking::whereIn('quotation_id', $quotations->pluck('id'))
            ->pluck('id', 'quotation_id');

        return view('livewire.admin.lead.create-quotation', [
            'lead' => $lead,
            'packages' => Package::query()->orderBy('created_at', 'desc')->get(['id', 'name']),
            'channels' => PaymentChannel::cases(),
            'quotations' => $quotations,
            'bookedQuotationIds' => $bookedQuotationIds,
        ]);
    }
}
