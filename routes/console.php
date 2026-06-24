<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

Artisan::command('people:dedupe {--apply : Soft-delete safe duplicate people records}', function () {
    $modules = [
        'owners' => [
            'model' => Owner::class,
            'label' => 'Owner',
            'links' => [
                ['owner_unit', 'owner_id'],
                ['owner_notes', 'owner_id'],
                ['expenses', 'owner_id'],
                ['owner_unit_contracts', 'owner_id'],
                ['owner_payout_transfers', 'owner_id'],
                ['support_tickets', 'owner_id'],
            ],
        ],
        'tenants' => [
            'model' => Tenant::class,
            'label' => 'Tenant',
            'links' => [
                ['bookings', 'tenant_id'],
                ['invoices', 'tenant_id'],
                ['payment_collection_requests', 'tenant_id'],
                ['booking_deposit_refunds', 'tenant_id'],
                ['support_tickets', 'tenant_id'],
            ],
        ],
        'agents' => [
            'model' => Agent::class,
            'label' => 'Agent',
            'links' => [
                ['bookings', 'agent_id'],
                ['support_tickets', 'agent_id'],
            ],
        ],
        'operations-team' => [
            'model' => OperationsTeamMember::class,
            'label' => 'Operations team',
            'links' => [
                ['booking_tasks', 'assigned_to_id'],
                ['payment_collection_requests', 'assigned_to_id'],
                ['vehicle_handovers', 'team_member_id'],
                ['support_tickets', 'operations_team_member_id'],
            ],
        ],
    ];

    $apply = (bool) $this->option('apply');
    $removed = 0;
    $skipped = 0;
    $found = 0;

    $duplicateKey = function (Model $record): ?string {
        $email = filled($record->email) ? Str::of($record->email)->lower()->trim()->toString() : null;
        $identity = filled($record->identity_no) ? Str::of($record->identity_no)->lower()->replace(['-', ' ', '.'], '')->trim()->toString() : null;
        $mobile = filled($record->mobile_no) ? preg_replace('/\D+/', '', $record->mobile_no) : null;
        $name = filled($record->full_name) ? Str::of($record->full_name)->squish()->lower()->toString() : null;

        return $email
            ? 'email:'.$email
            : ($identity ? 'identity:'.$identity : (($mobile && $name) ? 'mobile-name:'.$mobile.'|'.$name : null));
    };

    $hasLinks = function (Model $record, array $links): bool {
        if ($record->user_id) {
            return true;
        }

        foreach ($links as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $record->id)->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('person_notes') && DB::table('person_notes')->where('notable_type', $record::class)->where('notable_id', $record->id)->exists()) {
            return true;
        }

        return false;
    };

    foreach ($modules as $module => $config) {
        $modelClass = $config['model'];
        $records = $modelClass::query()->oldest('id')->get()->groupBy($duplicateKey)->filter(fn ($group, $key) => filled($key) && $group->count() > 1);

        foreach ($records as $key => $group) {
            $primary = $group->first();
            $duplicates = $group->slice(1);
            $found += $duplicates->count();
            $this->line("{$config['label']} duplicate group {$key}: keep #{$primary->id} {$primary->full_name}, duplicates ".$duplicates->pluck('id')->implode(', '));

            foreach ($duplicates as $duplicate) {
                if ($hasLinks($duplicate, $config['links'])) {
                    $skipped++;
                    $this->warn("  skipped #{$duplicate->id} {$duplicate->full_name}: linked records exist");
                    continue;
                }

                if ($apply) {
                    $duplicate->delete();
                    $removed++;
                    $this->info("  removed #{$duplicate->id} {$duplicate->full_name}");
                }
            }
        }
    }

    $mode = $apply ? 'applied' : 'preview';
    $this->info("People duplicate cleanup {$mode}. Found {$found}, removed {$removed}, skipped {$skipped}.");

    if (! $apply) {
        $this->warn('Run php artisan people:dedupe --apply to soft-delete safe duplicates.');
    }
})->purpose('Preview or safely soft-delete duplicate people records with no linked business data');
