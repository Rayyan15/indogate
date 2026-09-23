<?php

namespace App\Livewire\Admin\Finance;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class VendorPaymentList extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $partnerFilter = '';

    public bool $showModal = false;

    public ?int $booking_id = null;

    public ?int $partner_id = null;

    public int $amount_minor = 0;

    public string $currency = 'IDR';

    public float $fx_rate = 1.0;

    public string $paid_at = '';

    public string $description = '';

    public $proofFile;

    public ?string $actionSuccess = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'partnerFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('payment.verify'), 403);
        $this->paid_at = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPartnerFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['booking_id', 'partner_id', 'amount_minor', 'description', 'proofFile', 'actionSuccess']);
        $this->currency = 'IDR';
        $this->fx_rate = 1.0;
        $this->paid_at = now()->toDateString();
        $this->showModal = true;
    }

    public function updatedCurrency(): void
    {
        if (strtoupper($this->currency) === 'IDR') {
            $this->fx_rate = 1.0;
        } else {
            $rate = ExchangeRate::currentFor(strtoupper($this->currency));
            $this->fx_rate = $rate ? (float) $rate->rate : 0.0; // 0 fails validation instead of silently using 1.0
        }
    }

    public function save(): void
    {
        $this->validate([
            'partner_id' => [
                'required',
                Rule::exists('partners', 'id')->where('branch_id', CurrentBranch::id()),
            ],
            'booking_id' => [
                'nullable',
                Rule::exists('package_bookings', 'id')->where('branch_id', CurrentBranch::id()),
            ],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'fx_rate' => ['required', 'numeric', 'min:0.00000001'],
            'paid_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'proofFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->authorize('create', VendorPayment::class);

        $proofPath = null;
        if ($this->proofFile) {
            $proofPath = $this->proofFile->store('vendor-payment-proofs', 'local');
        }

        $curr = strtoupper($this->currency);
        $decimalPlaces = Currency::where('code', $curr)->value('decimal_places') ?? ($curr === 'IDR' ? 0 : 2);
        $factor = 10 ** $decimalPlaces;
        $idrEquivalentMinor = $curr === 'IDR'
            ? $this->amount_minor
            : (int) round(($this->amount_minor * (float) $this->fx_rate) / $factor);

        VendorPayment::create([
            'branch_id' => CurrentBranch::id(),
            'booking_id' => $this->booking_id,
            'partner_id' => $this->partner_id,
            'amount_minor' => $this->amount_minor,
            'currency' => strtoupper($this->currency),
            'fx_rate' => $this->fx_rate,
            'idr_equivalent_minor' => $idrEquivalentMinor,
            'description' => $this->description ?: null,
            'proof_file' => $proofPath,
            'paid_at' => $this->paid_at,
            'created_by' => Auth::id(),
        ]);

        activity('finance')
            ->causedBy(Auth::user())
            ->log('Recorded vendor payment');

        $this->showModal = false;
        $this->actionSuccess = __('finance.vendor_payment_recorded_success');
    }

    public function deleteVendorPayment(int $id): void
    {
        $payment = VendorPayment::findOrFail($id);
        $this->authorize('delete', $payment);

        if ($payment->proof_file && Storage::disk('local')->exists($payment->proof_file)) {
            Storage::disk('local')->delete($payment->proof_file);
        }

        $payment->delete();

        activity('finance')
            ->causedBy(Auth::user())
            ->log('Deleted vendor payment');

        $this->actionSuccess = 'Pembayaran vendor berhasil dihapus.';
    }

    public function render(): View
    {
        $query = VendorPayment::with(['partner', 'booking', 'creator'])
            ->when($this->partnerFilter !== '', fn ($q) => $q->where('partner_id', $this->partnerFilter))
            ->when($this->search !== '', function ($q) {
                $term = addcslashes($this->search, '%_\\');
                $q->where(function ($sq) use ($term) {
                    $sq->whereHas('partner', fn ($pq) => $pq->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('booking', fn ($bq) => $bq->where('code', 'like', "%{$term}%"))
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->latest('paid_at');

        $partners = Partner::where('is_active', true)->orderBy('name')->get();
        $recentBookings = PackageBooking::latest('id')->take(30)->get();

        return view('livewire.admin.finance.vendor-payment-list', [
            'vendorPayments' => $query->paginate(15),
            'partners' => $partners,
            'recentBookings' => $recentBookings,
        ]);
    }
}
