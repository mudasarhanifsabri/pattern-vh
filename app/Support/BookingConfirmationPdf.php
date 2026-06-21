<?php

namespace App\Support;

use App\Models\Booking;

class BookingConfirmationPdf
{
    public function make(Booking $booking): string
    {
        $booking->loadMissing(['unit.building', 'tenant', 'agent']);

        $pdf = new BrandedPdf('Booking Confirmation', "Booking {$booking->booking_no}");

        $pdf->labelValue(52, 640, 'Booking no', $booking->booking_no)
            ->labelValue(222, 640, 'Status', str($booking->booking_status)->headline())
            ->labelValue(392, 640, 'Type', str($booking->booking_type)->replace('_', ' ')->headline())
            ->labelValue(52, 580, 'Building', $booking->unit->building->name)
            ->labelValue(222, 580, 'Unit', 'Unit '.$booking->unit->unit_no)
            ->labelValue(392, 580, 'Guests', (string) $booking->guest_count)
            ->labelValue(52, 520, 'Tenant', $booking->tenant->full_name)
            ->labelValue(222, 520, 'Mobile', $booking->tenant->mobile_no)
            ->labelValue(392, 520, 'Agent', $booking->agent?->full_name ?: 'Direct booking')
            ->labelValue(52, 460, 'Check-in', $booking->check_in_date?->format('M d, Y').' '.($booking->check_in_time ?: ''))
            ->labelValue(222, 460, 'Check-out', $booking->check_out_date?->format('M d, Y').' '.($booking->check_out_time ?: ''))
            ->labelValue(392, 460, 'Source', $booking->source ?: 'Direct');

        $pdf->labelValue(52, 410, 'Signature status', $booking->confirmation_signed_at ? 'Signed by '.$booking->confirmation_signed_by : 'Not signed')
            ->labelValue(222, 410, 'Signed at', $booking->confirmation_signed_at?->format('M d, Y H:i') ?: 'Pending')
            ->labelValue(392, 410, 'Delivery', collect($booking->confirmation_delivery_channels ?? [])->implode(', ') ?: 'Not sent');

        $pdf->text(52, 370, 'Booking charges', 14, 'bold')
            ->table(52, 342, [
                ['Rent', 'AED '.number_format((float) $booking->rent_amount, 2)],
                ['VAT 5% on rent only', 'AED '.number_format((float) $booking->vat_amount, 2)],
                ['Security deposit', 'AED '.number_format((float) $booking->deposit_amount, 2)],
                ['DTCM fee', 'AED '.number_format((float) $booking->dtcm_fee, 2)],
                ['Cleaning fee', 'AED '.number_format((float) $booking->cleaning_fee, 2)],
                ['Agency fee', 'AED '.number_format((float) $booking->agency_fee, 2)],
            ])
            ->totalBox(376, 166, 'Total booking value', 'AED '.number_format((float) $booking->total_amount, 2))
            ->rect(52, 118, 500, 36, 'EFF6FF', 'BFDBFE')
            ->text(68, 139, 'Security note', 8, 'bold', '2563EB')
            ->text(68, 126, 'Building security receives tenant and booking check-in details after booking confirmation.', 9, 'regular', '0F172A');

        return $pdf->output();
    }
}
