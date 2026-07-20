<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\BookingDepositRefund;
use App\Models\BookingExtensionRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Support\TaxCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::query()->with('building')->orderBy('id')->get();

        if ($units->isEmpty()) {
            $this->command?->warn('No units found. Run DemoPortfolioSeeder first.');
            return;
        }

        $agent = Agent::query()->first();
        $unitA = $units->first();
        $unitB = $units->get(1) ?: $unitA;

        $rows = [
            [
                'booking_no' => 'BK-DEMO-0002',
                'invoice_no' => 'INV-DEMO-0002',
                'payment_no' => 'PAY-DEMO-0002',
                'tenant' => ['full_name' => 'Omar Nasser', 'email' => 'omar.demo.tenant@example.com', 'mobile_no' => '+971501230002', 'nationality' => 'Jordan'],
                'unit' => $unitB,
                'status' => 'checked_in',
                'check_in' => now()->subDays(2),
                'check_out' => now()->addDays(4),
                'guest_count' => 3,
                'rent' => 12800,
                'deposit' => 2000,
                'dtcm' => 90,
                'cleaning' => 300,
                'agency' => 0,
                'source' => 'Booking.com',
                'invoice_status' => 'paid',
                'paid' => true,
                'notes' => 'Demo active checked-in stay.',
            ],
            [
                'booking_no' => 'BK-DEMO-0003',
                'invoice_no' => 'INV-DEMO-0003',
                'tenant' => ['full_name' => 'Sofia Martin', 'email' => 'sofia.demo.tenant@example.com', 'mobile_no' => '+971501230003', 'nationality' => 'Spain'],
                'unit' => $unitA,
                'status' => 'confirmed',
                'check_in' => now()->addDays(12),
                'check_out' => now()->addDays(18),
                'guest_count' => 2,
                'rent' => 9200,
                'deposit' => 1500,
                'dtcm' => 75,
                'cleaning' => 250,
                'agency' => 460,
                'source' => 'Airbnb',
                'invoice_status' => 'sent',
                'paid' => false,
                'notes' => 'Demo upcoming confirmed booking with pending balance.',
            ],
            [
                'booking_no' => 'BK-DEMO-0004',
                'invoice_no' => 'INV-DEMO-0004',
                'tenant' => ['full_name' => 'Yuki Tanaka', 'email' => 'yuki.demo.tenant@example.com', 'mobile_no' => '+971501230004', 'nationality' => 'Japan'],
                'unit' => $unitA,
                'status' => 'checked_out',
                'check_in' => now()->subDays(22),
                'check_out' => now()->subDays(16),
                'guest_count' => 1,
                'rent' => 7800,
                'deposit' => 1200,
                'dtcm' => 75,
                'cleaning' => 250,
                'agency' => 0,
                'source' => 'Direct',
                'invoice_status' => 'paid',
                'paid' => true,
                'notes' => 'Demo completed booking with deposit workflow.',
                'refund' => true,
            ],
            [
                'booking_no' => 'BK-DEMO-0005',
                'invoice_no' => 'INV-DEMO-0005',
                'extension_invoice_no' => 'INV-DEMO-EXT-0005',
                'tenant' => ['full_name' => 'Maya Robinson', 'email' => 'maya.demo.tenant@example.com', 'mobile_no' => '+971501230005', 'nationality' => 'United Kingdom'],
                'unit' => $unitB,
                'status' => 'extended',
                'check_in' => now()->addDays(8),
                'check_out' => now()->addDays(13),
                'extended_to' => now()->addDays(16),
                'guest_count' => 4,
                'rent' => 15600,
                'extension_rent' => 3600,
                'deposit' => 2500,
                'dtcm' => 120,
                'cleaning' => 450,
                'agency' => 780,
                'source' => 'Agent referral',
                'invoice_status' => 'paid',
                'paid' => true,
                'notes' => 'Demo extended booking with separate extension invoice.',
            ],
        ];

        foreach ($rows as $row) {
            $tenant = Tenant::updateOrCreate(
                ['email' => $row['tenant']['email']],
                [
                    'full_name' => $row['tenant']['full_name'],
                    'mobile_no' => $row['tenant']['mobile_no'],
                    'mobile_has_whatsapp' => true,
                    'identity_type' => 'passport',
                    'identity_no' => 'DEMO-'.substr(md5($row['tenant']['email']), 0, 8),
                    'identity_expiry_date' => now()->addYears(2)->toDateString(),
                    'date_of_birth' => now()->subYears(34)->toDateString(),
                    'nationality' => $row['tenant']['nationality'],
                    'emergency_contact_name' => 'Demo Emergency Contact',
                    'emergency_contact_mobile' => '+971501239999',
                ],
            );

            $originalCheckout = $row['check_out']->copy()->startOfDay();
            $checkout = ($row['extended_to'] ?? $row['check_out'])->copy()->startOfDay();
            $rent = (float) $row['rent'];
            $vat = TaxCalculator::rentVat($rent);
            $total = $rent + $vat + (float) $row['deposit'] + (float) $row['dtcm'] + (float) $row['cleaning'] + (float) $row['agency'];

            $booking = Booking::updateOrCreate(
                ['booking_no' => $row['booking_no']],
                [
                    'booking_type' => 'holiday_home',
                    'unit_id' => $row['unit']->id,
                    'tenant_id' => $tenant->id,
                    'agent_id' => $agent?->id,
                    'check_in_date' => $row['check_in']->toDateString(),
                    'check_out_date' => $checkout->toDateString(),
                    'check_in_time' => '15:00',
                    'check_out_time' => '11:00',
                    'guest_count' => $row['guest_count'],
                    'rent_amount' => $rent,
                    'deposit_amount' => $row['deposit'],
                    'dtcm_fee' => $row['dtcm'],
                    'cleaning_fee' => $row['cleaning'],
                    'agency_fee' => $row['agency'],
                    'vat_amount' => $vat,
                    'total_amount' => $total,
                    'booking_status' => $row['status'],
                    'source' => $row['source'],
                    'notes' => $row['notes'],
                    'smart_lock_code_mode' => 'manual',
                    'smart_lock_code' => (string) random_int(100000, 999999),
                    'smart_lock_code_valid_from' => Carbon::parse($row['check_in']->toDateString().' 15:00'),
                    'smart_lock_code_valid_until' => Carbon::parse($checkout->toDateString().' 11:00'),
                    'smart_lock_code_note' => 'Demo access code.',
                ],
            );

            if (! empty($row['extension_invoice_no'])) {
                $extensionInvoiceIds = $booking->extensionRequests()->whereNotNull('invoice_id')->pluck('invoice_id');
                Payment::query()->whereIn('invoice_id', $extensionInvoiceIds)->delete();
                Invoice::withTrashed()->whereIn('id', $extensionInvoiceIds)->forceDelete();
                $booking->extensionRequests()->delete();
                Invoice::withTrashed()->where('booking_id', $booking->id)->where('invoice_no', '!=', $row['invoice_no'])->forceDelete();
            }

            $invoice = $this->upsertInvoice($booking, $row['invoice_no'], $rent, $row['deposit'], $row['dtcm'], $row['cleaning'], $row['agency'], $row['invoice_status'], $row['check_in'], $originalCheckout);

            if ($row['paid']) {
                Payment::updateOrCreate(
                    ['payment_no' => $row['payment_no'] ?? 'PAY-'.$row['booking_no']],
                    [
                        'invoice_id' => $invoice->id,
                        'booking_id' => $booking->id,
                        'method' => 'card_machine',
                        'status' => 'approved',
                        'amount' => $invoice->total_amount,
                        'paid_at' => now()->subDay(),
                        'reference_no' => 'DEMO-'.$row['booking_no'],
                        'notes' => 'Demo approved payment.',
                        'approved_at' => now()->subDay(),
                    ],
                );
            }

            if (! empty($row['extension_invoice_no'])) {
                $extensionRent = (float) $row['extension_rent'];
                $extension = BookingExtensionRequest::updateOrCreate(
                    ['booking_id' => $booking->id, 'requested_check_out_date' => $checkout->toDateString()],
                    [
                        'tenant_id' => $tenant->id,
                        'extra_rent_amount' => $extensionRent,
                        'status' => 'approved_pending_payment',
                        'approval_notes' => 'Demo extension approved from booking page.',
                        'approved_at' => now()->subHours(8),
                    ],
                );

                $extensionInvoice = $this->upsertInvoice($booking, $row['extension_invoice_no'], $extensionRent, 0, 0, 0, 0, 'sent', $originalCheckout, $checkout, 'Extension rent invoice.');
                $extension->update(['invoice_id' => $extensionInvoice->id]);
            }

            if (! empty($row['refund'])) {
                BookingDepositRefund::updateOrCreate(
                    ['booking_id' => $booking->id],
                    [
                        'tenant_id' => $tenant->id,
                        'deposit_amount' => $row['deposit'],
                        'damage_amount' => 150,
                        'refund_amount' => max(0, (float) $row['deposit'] - 150),
                        'status' => 'tenant_review',
                        'inspection_notes' => 'Demo checkout inspection completed.',
                        'damage_report' => 'Minor touch-up charge recorded for demo data.',
                        'inspection_completed_at' => now()->subDays(14),
                    ],
                );
            }
        }

        $this->command?->info('Demo bookings added.');
    }

    private function upsertInvoice(Booking $booking, string $invoiceNo, float $rent, float $deposit, float $dtcm, float $cleaning, float $agency, string $status, Carbon $periodStart, Carbon $periodEnd, string $notes = 'Demo booking invoice.'): Invoice
    {
        $vat = TaxCalculator::rentVat($rent);
        $total = $rent + $vat + $deposit + $dtcm + $cleaning + $agency;
        $paid = $status === 'paid' ? $total : 0;

        $invoice = Invoice::withTrashed()->firstOrNew(['invoice_no' => $invoiceNo]);
        $invoice->fill([
            'booking_id' => $booking->id,
            'tenant_id' => $booking->tenant_id,
            'unit_id' => $booking->unit_id,
            'invoice_date' => $periodStart->toDateString(),
            'due_date' => $periodStart->copy()->addDays(2)->toDateString(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'rent_amount' => $rent,
            'deposit_amount' => $deposit,
            'dtcm_fee' => $dtcm,
            'cleaning_fee' => $cleaning,
            'agency_fee' => $agency,
            'vat_amount' => $vat,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_amount' => max(0, $total - $paid),
            'status' => $status,
            'notes' => $notes,
            'deleted_at' => null,
        ])->save();

        return $invoice;
    }
}
