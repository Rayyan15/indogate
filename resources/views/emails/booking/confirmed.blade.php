<x-mail::message>
# Booking Confirmed!

Dear {{ $booking->customer->full_name ?? 'Valued Customer' }},

Thank you for choosing **Indogate** for your journey to Indonesia. We are pleased to inform you that your booking **#{{ substr($booking->booking_number, 0, 8) }}** has been successfully confirmed and your payment has been verified.

## Booking Summary
**Status:** Confirmed
**Total Amount:** IDR {{ number_format($booking->total_amount) }}

<x-mail::button :url="route('customer.bookings.show', $booking->id)">
View Booking Details
</x-mail::button>

Our premium, Arabic-speaking team is ready to assist you. If you have requested a private driver, you can view their details in your dashboard before your arrival.

If you have any questions, feel free to reply to this email.

Warm regards,
**The Indogate Team**
</x-mail::message>
