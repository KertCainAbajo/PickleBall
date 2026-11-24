<?php

namespace App\Livewire\Concerns;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Services\BookingService;

/**
 * Inline "Record payment" form for admin screens.
 * The component must have a public ?string $notice.
 */
trait RecordsPayments
{
    /** Booking whose payment form is open. */
    public ?int $payingId = null;

    public string $paymentMethod = 'cash';

    public string $paymentReference = '';

    public function startPayment(int $bookingId): void
    {
        $this->resetErrorBag();
        $this->payingId = $bookingId;
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->paymentReference = '';
    }

    public function cancelPayment(): void
    {
        $this->reset('payingId', 'paymentReference');
        $this->resetErrorBag();
    }

    public function recordPayment(BookingService $bookings): void
    {
        $this->validate([
            'paymentMethod' => ['required', 'in:'.implode(',', array_column(PaymentMethod::cases(), 'value'))],
            'paymentReference' => ['nullable', 'string', 'max:100'],
        ]);

        $booking = $bookings->recordPayment(
            Booking::findOrFail($this->payingId),
            PaymentMethod::from($this->paymentMethod),
            $this->paymentReference,
        );

        $this->cancelPayment();
        $this->notice = sprintf('Payment of ₱%s recorded for %s. You can confirm it now.',
            number_format((float) $booking->amount_paid, 2), $booking->reference);
    }
}
