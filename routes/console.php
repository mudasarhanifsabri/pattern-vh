<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\Schedule;
use Minishlink\WebPush\VAPID;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('webpush:vapid', function () {
    try {
        $keys = VAPID::createVapidKeys();
    } catch (Throwable $exception) {
        $this->error('VAPID key generation failed on this PHP/OpenSSL build: '.$exception->getMessage());
        $this->warn('On cPanel this usually works. If it fails there too, generate keys from a machine with OpenSSL P-256 support and paste them into .env.');

        return 1;
    }

    $this->line('Add these values to your .env:');
    $this->newLine();
    $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
    $this->line('VAPID_SUBJECT='.config('app.url'));
})->purpose('Generate VAPID keys for browser push notifications');

Artisan::command('bookings:send-reminders', function () {
    $count = 0;

    Booking::query()
        ->with(['tenant', 'unit.building'])
        ->whereIn('booking_status', ['confirmed', 'checked_in'])
        ->whereIn('check_out_date', [now()->addDays(7)->toDateString(), now()->addDays(3)->toDateString()])
        ->each(function (Booking $booking) use (&$count): void {
            $days = now()->startOfDay()->diffInDays($booking->check_out_date->startOfDay());
            foreach (['email', 'whatsapp', 'push'] as $channel) {
                $booking->notificationLogs()->firstOrCreate(
                    ['channel' => $channel, 'subject' => "Checkout reminder {$days} days"],
                    [
                        'recipient' => match ($channel) {
                            'email' => $booking->tenant->email,
                            'push' => $booking->tenant->user_id ? 'user:'.$booking->tenant->user_id : $booking->tenant->email,
                            default => $booking->tenant->mobile_no,
                        },
                        'message' => "Your booking {$booking->booking_no} checks out in {$days} days. Please confirm checkout or request an extension in your tenant portal.",
                        'status' => $channel === 'email' ? 'queued' : 'pending',
                        'payload' => [
                            'booking_id' => $booking->id,
                            'days_before_checkout' => $days,
                            'actions' => ['request_extension', 'confirm_checkout'],
                            'url' => route('dashboard'),
                            'integration_ready' => true,
                        ],
                        'sent_at' => $channel === 'email' ? now() : null,
                    ],
                );
                $count++;
            }
        });

    $this->info("Booking reminder logs prepared: {$count}");
})->purpose('Prepare 7-day and 3-day booking checkout/extension reminder logs');

Schedule::command('bookings:send-reminders')->dailyAt('09:00');

Artisan::command('invoices:send-reminders', function () {
    $count = 0;

    Invoice::query()
        ->with(['booking.tenant', 'booking.unit.building', 'tenant'])
        ->whereIn('status', ['sent', 'partially_paid'])
        ->where('balance_amount', '>', 0)
        ->whereIn('due_date', [today()->toDateString(), now()->addDays(3)->toDateString(), now()->addDays(7)->toDateString()])
        ->each(function (Invoice $invoice) use (&$count): void {
            $tenant = $invoice->tenant ?: $invoice->booking?->tenant;
            if (! $tenant || ! $invoice->booking) {
                return;
            }

            $days = today()->diffInDays($invoice->due_date, false);
            $label = $days === 0 ? 'today' : "in {$days} days";

            foreach (['email', 'push'] as $channel) {
                $invoice->booking->notificationLogs()->firstOrCreate(
                    ['channel' => $channel, 'subject' => "Invoice reminder {$invoice->invoice_no}"],
                    [
                        'recipient' => $channel === 'email'
                            ? $tenant->email
                            : ($tenant->user_id ? 'user:'.$tenant->user_id : $tenant->email),
                        'message' => "Invoice {$invoice->invoice_no} for AED ".number_format((float) $invoice->balance_amount, 2)." is due {$label}.",
                        'status' => 'queued',
                        'payload' => [
                            'invoice_id' => $invoice->id,
                            'due_date' => $invoice->due_date?->toDateString(),
                            'balance_amount' => $invoice->balance_amount,
                            'url' => route('dashboard'),
                            'integration_ready' => true,
                        ],
                    ],
                );
                $count++;
            }
        });

    $this->info("Invoice reminder logs prepared: {$count}");
})->purpose('Prepare due invoice reminders for tenants');

Schedule::command('invoices:send-reminders')->dailyAt('10:00');
