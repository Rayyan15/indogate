<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use Illuminate\Http\Request;
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

    public function store(Request $request)
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
            return redirect()->route('customer.checkout.index')->with('warning', 'Beberapa item dalam keranjang belanja telah diperbarui atau dihapus.');
        }

        DB::beginTransaction();
        try {
            $customer = Auth::user()->customer;
            if (! $customer) {
                $customer = Customer::create([
                    'user_id' => Auth::id(),
                    'full_name' => Auth::user()->name,
                ]);
            }

            $booking = Booking::create([
                'booking_number' => Str::uuid(),
                'customer_id' => $customer->id,
                'status' => 'pending_payment',
                'total_amount' => $totalAmount,
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

            session()->forget('cart');
            DB::commit();

            return redirect()->route('customer.bookings.show', $booking)->with('success', 'Pemesanan berhasil dibuat. Silakan lanjutkan ke pembayaran.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Checkout gagal: '.$e->getMessage());
        }
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
                $price = CartController::resolveItemPrice($item['bookable_type'], (int) $item['bookable_id']);
                $quantity = max(1, (int) ($item['quantity'] ?? 1));

                $item['price'] = $price;
                $item['quantity'] = $quantity;

                $validCart[] = $item;
                $totalAmount += $price * $quantity;
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException|\InvalidArgumentException $e) {
                $hasRemovedItems = true;
            }
        }

        return [$validCart, $totalAmount, $hasRemovedItems];
    }
}
