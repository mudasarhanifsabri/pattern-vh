<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\TtLockSetting;
use App\Support\TtLockApi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Minishlink\WebPush\VAPID;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

Artisan::command('permissions:repair', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permissions = [
        'reports.view',
        'reports.export',
        'accounting.view',
        'accounting.manage',
        'portal.owner',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    Role::findOrCreate('Super Admin', 'web')->givePermissionTo($permissions);
    Role::findOrCreate('Operations Team', 'web')->givePermissionTo($permissions);
    Role::findOrCreate('Owner', 'web')->givePermissionTo(['reports.view', 'portal.owner']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->info('Report/accounting permissions repaired for Super Admin, Operations Team, and Owner roles.');
})->purpose('Repair report and portal permissions after deployment');

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
        ->where(function ($query): void {
            $query->whereIn('due_date', [today()->toDateString(), now()->addDays(3)->toDateString(), now()->addDays(7)->toDateString()])
                ->orWhereDate('due_date', '<', today());
        })
        ->each(function (Invoice $invoice) use (&$count): void {
            $tenant = $invoice->tenant ?: $invoice->booking?->tenant;
            if (! $tenant || ! $invoice->booking) {
                return;
            }

            $days = today()->diffInDays($invoice->due_date, false);
            $stage = match (true) {
                $days < 0 => 'overdue',
                $days === 0 => 'due-today',
                default => 'due-in-'.$days.'-days',
            };
            $label = match (true) {
                $days < 0 => abs($days).' '.str('day')->plural(abs($days)).' overdue',
                $days === 0 => 'due today',
                default => "due in {$days} ".str('day')->plural($days),
            };

            foreach (['email', 'push'] as $channel) {
                $invoice->booking->notificationLogs()->firstOrCreate(
                    ['channel' => $channel, 'subject' => "Invoice {$stage} {$invoice->invoice_no}"],
                    [
                        'recipient' => $channel === 'email'
                            ? $tenant->email
                            : ($tenant->user_id ? 'user:'.$tenant->user_id : $tenant->email),
                        'message' => "Invoice {$invoice->invoice_no} for AED ".number_format((float) $invoice->balance_amount, 2)." is {$label}.",
                        'status' => 'queued',
                        'payload' => [
                            'invoice_id' => $invoice->id,
                            'due_date' => $invoice->due_date?->toDateString(),
                            'balance_amount' => $invoice->balance_amount,
                            'url' => route('invoices.show', $invoice),
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

Artisan::command('ttlock:sync-auto {--history-days=2 : Number of history days to sync}', function (TtLockApi $api) {
    if (! Schema::hasTable('tt_lock_settings') || ! Schema::hasTable('tt_locks')) {
        $this->warn('TTLock tables are not ready.');

        return 0;
    }

    $days = max(1, min(30, (int) $this->option('history-days')));
    $groups = TtLockSetting::query()->where('is_active', true)->get();

    if ($groups->isEmpty()) {
        $this->info('No active TTLock API groups to sync.');

        return 0;
    }

    $lockTotal = 0;
    $historyTotal = 0;

    foreach ($groups as $group) {
        try {
            $lockResult = $api->syncLocks($group);
            $historyResult = $api->syncHistory($group->fresh(), $days);
            $lockTotal += (int) ($lockResult['synced'] ?? 0);
            $historyTotal += (int) ($historyResult['synced'] ?? 0);
            $this->info("{$group->name}: {$lockResult['synced']} locks, {$historyResult['synced']} history records.");
        } catch (Throwable $exception) {
            $group->forceFill([
                'last_tested_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();

            $this->error("{$group->name}: ".$exception->getMessage());
        }
    }

    $this->info("TTLock auto sync completed. Locks: {$lockTotal}. History records: {$historyTotal}.");

    return 0;
})->purpose('Auto-sync active TTLock groups, locks, and recent access history');

Schedule::command('ttlock:sync-auto --history-days=2')->hourly();

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
