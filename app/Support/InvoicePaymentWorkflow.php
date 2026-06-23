<?php

namespace App\Support;

use App\Mail\ReceiptIssuedMail;
use App\Mail\BookingSecurityCheckInMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvoicePaymentWorkflow
{
    public function afterPayment(Payment $payment): ?Receipt
    {
        $invoice = $payment->invoice()->with(['payments', 'booking.tenant', 'booking.unit.building'])->firstOrFail();
        $paid = (float) $invoice->payments()->where('status', 'approved')->sum('amount');
        $balance = max(0, (float) $invoice->total_amount - $paid);

        $invoice->forceFill([
            'paid_amount' => $paid,
            'balance_amount' => $balance,
            'status' => $balance <= 0 ? 'paid' : 'partially_paid',
        ])->save();

        if ($invoice->status !== 'paid' || $payment->status !== 'approved') {
            return null;
        }

        $booking = $invoice->booking;
        $booking->forceFill(['booking_status' => 'confirmed'])->save();

        app(BookingWorkflow::class)->afterSaved($booking->fresh(['unit', 'tenant']));
        $this->applyPaidExtension($invoice);
        $this->prepareAuthorityCheckin($booking);

        $receipt = Receipt::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'invoice_id' => $invoice->id,
                'booking_id' => $booking->id,
                'receipt_no' => $this->nextReceiptNo(),
                'check_in_code' => strtoupper(Str::random(8)),
                'amount' => $payment->amount,
                'issued_at' => now(),
            ],
        );

        $this->logReceiptNotifications($receipt);

        if ($booking->tenant->email && ! $receipt->emailed_at) {
            Mail::to($booking->tenant->email)->queue(new ReceiptIssuedMail($receipt->load(['booking.tenant', 'booking.unit.building'])));
            $receipt->forceFill(['emailed_at' => now()])->save();
        }

        return $receipt;
    }

    private function prepareAuthorityCheckin(Booking $booking): void
    {
        $booking->dtcmCheckin()->firstOrCreate([], [
            'status' => 'pending',
            'notes' => 'Booking paid. Submit tenant and booking details in the DTCM authority portal.',
        ]);
    }

    private function applyPaidExtension(Invoice $invoice): void
    {
        $extension = $invoice->extensionRequest()->with('booking.unit.building')->first();

        if (! $extension || $extension->status === 'paid_extended') {
            return;
        }

        $extension->booking->update([
            'check_out_date' => $extension->requested_check_out_date,
            'booking_status' => 'confirmed',
        ]);

        $extension->update(['status' => 'paid_extended']);

        $extension->booking->notificationLogs()->create([
            'channel' => 'email',
            'recipient' => implode(', ', $extension->booking->unit->building->security_emails ?? []),
            'subject' => 'Building security extension details',
            'message' => "Booking {$extension->booking->booking_no} extended until {$extension->requested_check_out_date->format('M d, Y')} after payment confirmation.",
            'status' => 'sent',
            'payload' => ['extension_request_id' => $extension->id, 'invoice_id' => $invoice->id],
            'sent_at' => now(),
        ]);

        $securityEmails = collect($extension->booking->unit->building->security_emails ?? [])->filter()->values();
        if ($securityEmails->isNotEmpty()) {
            Mail::to($securityEmails->all())->queue(new BookingSecurityCheckInMail($extension->booking->fresh(['unit.building', 'tenant'])));
        }
    }

    private function logReceiptNotifications(Receipt $receipt): void
    {
        $booking = $receipt->booking()->with('tenant')->firstOrFail();

        foreach (['email', 'whatsapp', 'push'] as $channel) {
            $booking->notificationLogs()->firstOrCreate(
                ['channel' => $channel, 'subject' => 'Receipt issued'],
                [
                    'recipient' => match ($channel) {
                        'email' => $booking->tenant->email,
                        'push' => $booking->tenant->user_id ? 'user:'.$booking->tenant->user_id : $booking->tenant->email,
                        default => $booking->tenant->mobile_no,
                    },
                    'message' => "Receipt {$receipt->receipt_no} issued. Check-in code: {$receipt->check_in_code}.",
                    'status' => $channel === 'email' ? 'sent' : 'pending',
                    'payload' => [
                        'receipt_id' => $receipt->id,
                        'check_in_code' => $receipt->check_in_code,
                        'url' => route('dashboard'),
                        'integration_ready' => true,
                    ],
                    'sent_at' => $channel === 'email' ? now() : null,
                ],
            );
        }
    }

    private function nextReceiptNo(): string
    {
        return 'RC-'.now()->format('Ymd').'-'.str_pad((string) (Receipt::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
