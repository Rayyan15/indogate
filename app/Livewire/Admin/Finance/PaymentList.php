<?php

namespace App\Livewire\Admin\Finance;

use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public bool $showRejectModal = false;

    #[Locked]
    public ?int $selectedPaymentId = null;

    public string $rejectionReason = '';

    public ?string $actionError = null;

    public ?string $actionSuccess = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(
            Auth::user()->can('payment.verify') || Auth::user()->can('booking.manage'),
            403
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function verifyPayment(int $paymentId): void
    {
        $this->reset(['actionError', 'actionSuccess']);

        $payment = Payment::findOrFail($paymentId);
        $this->authorize('verify', $payment);

        try {
            (new PaymentService)->verifyPayment($payment, Auth::user());
            $this->actionSuccess = __('finance.payment_verified_success');
        } catch (SelfApprovalException $e) {
            $this->actionError = $e->getMessage();
        } catch (PaymentVerificationException $e) {
            $this->actionError = $e->getMessage();
        } catch (\Exception $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function openRejectModal(int $paymentId): void
    {
        $this->reset(['actionError', 'actionSuccess']);
        $this->selectedPaymentId = $paymentId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function rejectPayment(): void
    {
        $this->reset(['actionError', 'actionSuccess']);
        $this->validate([
            'rejectionReason' => ['required', 'string', 'max:500'],
        ]);

        $payment = Payment::findOrFail($this->selectedPaymentId);
        $this->authorize('verify', $payment);

        try {
            (new PaymentService)->rejectPayment($payment, $this->rejectionReason, Auth::user());
            $this->showRejectModal = false;
            $this->actionSuccess = __('finance.payment_rejected_success');
            $this->reset(['selectedPaymentId', 'rejectionReason']);
        } catch (\Exception $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function proofUrl(Payment $payment): ?string
    {
        if (! $payment->proof_file) {
            return null;
        }

        return URL::temporarySignedRoute(
            'admin.finance.proofs.download',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );
    }

    public function receiptUrl(Payment $payment): string
    {
        return route('admin.finance.receipt', ['payment' => $payment->id]);
    }

    public function render(): View
    {
        $query = Payment::with(['booking.quotation.lead', 'creator', 'verifiedByUser'])
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter !== '', fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->search !== '', function ($q) {
                $term = addcslashes($this->search, '%_\\');
                $q->where(function ($sq) use ($term) {
                    $sq->whereHas('booking', fn ($bq) => $bq->where('code', 'like', "%{$term}%"))
                        ->orWhereHas('booking.quotation.lead', fn ($lq) => $lq->where('name', 'like', "%{$term}%"))
                        ->orWhere('channel', 'like', "%{$term}%");
                });
            })
            ->latest('id');

        return view('livewire.admin.finance.payment-list', [
            'payments' => $query->paginate(15),
        ]);
    }
}
