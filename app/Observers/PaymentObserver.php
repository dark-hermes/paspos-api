<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $order = $payment->order;
        $totalPaid = $order->payments()->sum('amount');

        if ($totalPaid >= $order->total_amount) {
            $order->update(['payment_status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $order->update(['payment_status' => 'partial']);
        } else {
            $order->update(['payment_status' => 'unpaid']);
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
