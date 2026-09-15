<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $totalAmount = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return view('customer.checkout.index', compact('cart', 'totalAmount'));
    }

    public function store(Request $request)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $totalAmount = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        DB::beginTransaction();
        try {
            $customer = Auth::user()->customer;
            if (!$customer) {
                $customer = \App\Models\Customer::create([
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

            foreach ($cart as $item) {
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

            return redirect()->route('customer.bookings.show', $booking)->with('success', 'Booking created successfully. Please proceed to payment.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }
}
