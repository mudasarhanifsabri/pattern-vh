<?php

namespace Database\Seeders;

use App\Models\AutoReplyRule;
use App\Models\Booking;
use App\Models\QuickReply;
use App\Models\SupportCategory;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SupportCenterSeeder extends Seeder
{
    public function run(): void
    {
        $categoryNames = ['Booking', 'Payment', 'Security Deposit', 'Owner Payout', 'Maintenance', 'Check-in', 'Check-out', 'Documents', 'General'];
        $categories = collect($categoryNames)->mapWithKeys(function (string $name, int $index): array {
            $category = SupportCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'color' => ['blue', 'emerald', 'amber', 'violet'][$index % 4], 'is_active' => true, 'sort_order' => $index + 1],
            );

            return [$category->slug => $category];
        });

        $rules = [
            ['deposit', ['deposit', 'security deposit'], 'Your security deposit is refundable after check-out inspection. If no damages or pending payments are found, refund will be processed as per company policy.', 'security-deposit'],
            ['owner payout', ['payout', 'owner payout'], 'Owner payout is processed after rent collection, deductions, maintenance, utilities, and management fee calculation. You can check payout status from your Owner Dashboard.', 'owner-payout'],
            ['booking', ['booking'], 'Your booking details are available in your tenant portal. Please check your booking confirmation, payment status, and check-in instructions.', 'booking'],
            ['check in', ['check in', 'check-in'], 'Check-in instructions become available after payment confirmation and DTCM guest registration. Please review your booking portal for the latest status.', 'check-in'],
            ['check out', ['check out', 'check-out'], 'You can confirm checkout from your tenant portal. After checkout, cleaning, inspection, and security deposit review will begin.', 'check-out'],
            ['maintenance', ['maintenance', 'repair', 'technician'], 'Your maintenance request has been received. Please share the unit, issue details, urgency, and clear photos so the operations team can assign a technician.', 'maintenance'],
            ['payment', ['payment', 'invoice'], 'Please open your invoice in the portal to review the outstanding balance. You may upload payment proof or request doorstep cash/card-machine collection.', 'payment'],
            ['contract', ['contract'], 'Your signed and pending contracts are available in your portal. Open the contract record to review the PDF and signature status.', 'documents'],
        ];

        foreach ($rules as [$name, $keywords, $response, $categorySlug]) {
            AutoReplyRule::updateOrCreate(
                ['name' => Str::headline($name)],
                ['keywords' => $keywords, 'response' => $response, 'support_category_id' => $categories[$categorySlug]?->id, 'priority' => 100, 'is_active' => true],
            );

            QuickReply::updateOrCreate(
                ['title' => Str::headline($name)],
                ['body' => $response, 'support_category_id' => $categories[$categorySlug]?->id, 'is_active' => true],
            );
        }

        $tenant = Tenant::where('email', 'nora.tenant@example.com')->first();
        $booking = Booking::where('booking_no', 'BK-DEMO-0001')->first();
        $admin = User::where('email', 'admin@example.com')->first();

        if (! $tenant || ! $admin) {
            return;
        }

        $ticket = SupportTicket::updateOrCreate(
            ['ticket_no' => 'SUP-DEMO-0001'],
            [
                'public_token' => Str::random(48),
                'mode' => 'chat',
                'requester_user_id' => $tenant->user_id,
                'requester_type' => 'existing',
                'requester_role' => 'Tenant',
                'requester_name' => $tenant->full_name,
                'requester_email' => $tenant->email,
                'requester_mobile' => $tenant->mobile_no,
                'support_category_id' => $categories['security-deposit']->id,
                'subject' => 'Security deposit refund timing',
                'description' => 'When will my security deposit be refunded?',
                'priority' => 'medium',
                'status' => 'in_progress',
                'channel' => 'portal',
                'assigned_to' => $admin->id,
                'booking_id' => $booking?->id,
                'unit_id' => $booking?->unit_id,
                'tenant_id' => $tenant->id,
                'first_response_at' => now(),
                'last_response_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        );

        SupportMessage::firstOrCreate(
            ['support_ticket_id' => $ticket->id, 'body' => 'When will my security deposit be refunded?'],
            ['sender_type' => 'customer', 'sender_name' => $tenant->full_name],
        );
        SupportMessage::firstOrCreate(
            ['support_ticket_id' => $ticket->id, 'body' => 'Your security deposit is refundable after check-out inspection. If no damages or pending payments are found, refund will be processed as per company policy.'],
            ['sender_type' => 'bot', 'sender_name' => 'Pattern Help Bot', 'is_auto_reply' => true],
        );
    }
}
