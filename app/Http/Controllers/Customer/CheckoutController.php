<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang belanja Anda kosong.');
        }

        [$validCart, $totalAmount, $hasRemovedItems] = $this->resolveAndSanitizeCart($cart);

        session()->put('cart', $validCart);

        if (empty($validCart)) {
            return redirect()->route('cart.index')->with('error', 'Item di keranjang belanja Anda sudah tidak tersedia.');
        }

        if ($hasRemovedItems) {
            session()->flash('warning', 'Beberapa item dalam keranjang belanja sudah tidak tersedia dan telah dihapus.');
        }

        return view('customer.checkout.index', [
            'cart' => $validCart,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function store()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang belanja Anda kosong.');
        }

        [$validCart, $totalAmount, $hasRemovedItems] = $this->resolveAndSanitizeCart($cart);

        session()->put('cart', $validCart);

        if (empty($validCart)) {
            return redirect()->route('cart.index')->with('error', 'Item di keranjang belanja Anda sudah tidak tersedia.');
        }

        if ($hasRemovedItems) {
            return redirect()->route('checkout.index')->with('warning', 'Beberapa item dalam keranjang belanja telah diperbarui atau dihapus.');
        }

        // One booking belongs to one branch (the branch that owns the
        // services), so its admins can see and verify it.
        $branchIds = array_unique(array_column($validCart, 'branch_id'));
        if (count($branchIds) > 1) {
            return redirect()->route('cart.index')->with('error', 'Item dari cabang berbeda harus dipesan terpisah.');
        }

        try {
            $booking = DB::transaction(function () use ($validCart, $totalAmount, $branchIds) {
                $user = Auth::user();
                $customer = $user->customer()->firstOrCreate([], ['full_name' => $user->name]);

                $booking = Booking::create([
                    'branch_id' => $branchIds[0],
                    'booking_number' => Str::uuid(),
                    'customer_id' => $customer->id,
                    'status' => Booking::STATUS_PENDING_PAYMENT,
                    'total_amount' => (int) round($totalAmount),
                    'currency' => 'IDR',
                ]);

                foreach ($validCart as $item) {
                    BookingItem::create([
                        'booking_id' => $booking->id,
                        'bookable_type' => $item['bookable_type'],
                        'bookable_id' => $item['bookable_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ]);
                }

                return $booking;
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Checkout gagal. Silakan coba lagi atau hubungi kami.');
        }

        session()->forget('cart');

        return redirect()->route('customer.bookings.show', $booking)->with('success', 'Pemesanan berhasil dibuat. Silakan lanjutkan ke pembayaran.');
    }

    /**
     * Memvalidasi ketersediaan dan menghitung ulang harga setiap item di cart.
     * Item yang sudah terhapus di database akan dibuang secara graceful.
     *
     * @param  array<int, array<string, mixed>>  $cart
     * @return array{0: array<int, array<string, mixed>>, 1: float, 2: bool}
     */
    protected function resolveAndSanitizeCart(array $cart): array
    {
        $validCart = [];
        $totalAmount = 0.0;
        $hasRemovedItems = false;

        foreach ($cart as $item) {
            if (! isset($item['bookable_type'], $item['bookable_id'])) {
                $hasRemovedItems = true;

                continue;
            }

            try {
                $resolved = CartController::resolveItem($item['bookable_type'], (int) $item['bookable_id']);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                $item['price'] = $resolved['price'];
                $item['branch_id'] = $resolved['branch_id'];
                $item['quantity'] = $quantity;

                $validCart[] = $item;
                $totalAmount += $resolved['price'] * $quantity;
            } catch (ModelNotFoundException|\InvalidArgumentException $e) {
                $hasRemovedItems = true;
            }
        }

        return [$validCart, $totalAmount, $hasRemovedItems];
    }
}
