<?php

namespace App\Support;

use App\Mail\BookingSecurityCheckInMail;
use App\Models\Booking;
use App\Models\OperationsTeamMember;
use Illuminate\Support\Facades\Mail;

class BookingWorkflow
{
    public function afterSaved(Booking $booking): void
    {
        if (! in_array($booking->booking_status, ['confirmed', 'checked_in'], true)) {
            return;
        }

        $this->createCheckoutTasks($booking);
        $this->createNotificationLogs($booking);
        $this->sendSecurityCheckInEmail($booking);
    }

    private function createCheckoutTasks(Booking $booking): void
    {
        $cleaner = OperationsTeamMember::query()
            ->where('team_role', 'cleaner')
            ->where('auto_assign_checkout_cleaning', true)
            ->where('availability_status', 'available')
            ->first();

        $technician = OperationsTeamMember::query()
            ->where('team_role', 'technician')
            ->where('auto_assign_checkout_inspection', true)
            ->where('availability_status', 'available')
            ->first();

        $cleaningTask = $booking->tasks()->firstOrCreate(
            ['task_type' => 'checkout_cleaning'],
            [
                'unit_id' => $booking->unit_id,
                'assigned_to_id' => $cleaner?->id,
                'title' => "Checkout cleaning for Unit {$booking->unit->unit_no}",
                'due_at' => $booking->check_out_date?->copy()->setTime(11, 0),
                'status' => 'open',
                'notes' => 'Auto-created when booking was confirmed.',
            ],
        );

        if ($cleaningTask->wasRecentlyCreated) {
            $cleaningTask->events()->create([
                'event_type' => 'auto_created',
                'description' => 'Checkout cleaning task auto-created when booking was confirmed.',
            ]);
        }

        $inspectionTask = $booking->tasks()->firstOrCreate(
            ['task_type' => 'checkout_inspection'],
            [
                'unit_id' => $booking->unit_id,
                'assigned_to_id' => $technician?->id,
                'title' => "Checkout inspection for Unit {$booking->unit->unit_no}",
                'due_at' => $booking->check_out_date?->copy()->setTime(15, 0),
                'status' => 'open',
                'notes' => 'Auto-created when booking was confirmed.',
            ],
        );

        if ($inspectionTask->wasRecentlyCreated) {
            $inspectionTask->events()->create([
                'event_type' => 'auto_created',
                'description' => 'Checkout inspection task auto-created when booking was confirmed.',
            ]);
        }
    }

    private function createNotificationLogs(Booking $booking): void
    {
        $booking->loadMissing(['tenant', 'unit.building']);

        foreach (['email', 'whatsapp', 'push'] as $channel) {
            $booking->notificationLogs()->firstOrCreate(
                ['channel' => $channel, 'subject' => 'Booking confirmation'],
                [
                    'recipient' => $channel === 'email' ? $booking->tenant->email : $booking->tenant->mobile_no,
                    'message' => "Booking {$booking->booking_no} confirmed for {$booking->unit->building->name} Unit {$booking->unit->unit_no}.",
                    'status' => 'pending',
                    'payload' => [
                        'booking_no' => $booking->booking_no,
                        'unit_id' => $booking->unit_id,
                        'tenant_id' => $booking->tenant_id,
                        'integration_ready' => true,
                    ],
                ],
            );
        }
    }

    private function sendSecurityCheckInEmail(Booking $booking): void
    {
        $booking->loadMissing(['tenant', 'unit.building']);

        $securityEmails = collect($booking->unit->building->security_emails ?? [])
            ->filter()
            ->unique()
            ->values();

        if ($securityEmails->isEmpty()) {
            return;
        }

        $log = $booking->notificationLogs()->firstOrCreate(
            ['channel' => 'email', 'subject' => 'Building security check-in details'],
            [
                'recipient' => $securityEmails->implode(', '),
                'message' => "Security check-in details sent for {$booking->unit->building->name} Unit {$booking->unit->unit_no}.",
                'status' => 'sent',
                'payload' => [
                    'security_emails' => $securityEmails->all(),
                    'booking_no' => $booking->booking_no,
                    'tenant_id' => $booking->tenant_id,
                    'unit_id' => $booking->unit_id,
                    'attachments' => ['booking_confirmation_pdf', 'tenant_document_if_available', 'dtcm_permit_if_available'],
                ],
                'sent_at' => now(),
            ],
        );

        if ($log->wasRecentlyCreated) {
            Mail::to($securityEmails->all())->queue(new BookingSecurityCheckInMail($booking));
            $log->forceFill(['status' => 'sent', 'sent_at' => now()])->save();
        }
    }
}
