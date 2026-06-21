<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Receipt;

class SimpleFinancePdf
{
    public function invoice(Invoice $invoice): string
    {
        $invoice->loadMissing(['booking.unit.building', 'tenant']);

        $pdf = new BrandedPdf('Invoice', $invoice->invoice_no);

        $pdf->labelValue(52, 640, 'Invoice no', $invoice->invoice_no)
            ->labelValue(222, 640, 'Status', str($invoice->status)->replace('_', ' ')->headline())
            ->labelValue(392, 640, 'Due date', $invoice->due_date?->format('M d, Y') ?: 'On receipt')
            ->labelValue(52, 580, 'Tenant', $invoice->tenant->full_name)
            ->labelValue(222, 580, 'Booking', $invoice->booking->booking_no)
            ->labelValue(392, 580, 'Invoice date', $invoice->invoice_date?->format('M d, Y') ?: now()->format('M d, Y'))
            ->labelValue(52, 520, 'Property', $invoice->booking->unit->building->name, 330)
            ->labelValue(392, 520, 'Unit', 'Unit '.$invoice->booking->unit->unit_no);

        $pdf->text(52, 478, 'Invoice summary', 14, 'bold')
            ->table(52, 450, [
                ['Rent', 'AED '.number_format((float) $invoice->rent_amount, 2)],
                ['VAT 5% on rent only', 'AED '.number_format((float) $invoice->vat_amount, 2)],
                ['Security deposit', 'AED '.number_format((float) $invoice->deposit_amount, 2)],
                ['DTCM fee', 'AED '.number_format((float) $invoice->dtcm_fee, 2)],
                ['Cleaning fee', 'AED '.number_format((float) $invoice->cleaning_fee, 2)],
                ['Agency fee', 'AED '.number_format((float) $invoice->agency_fee, 2)],
            ])
            ->totalBox(52, 184, 'Total', 'AED '.number_format((float) $invoice->total_amount, 2), '2563EB')
            ->totalBox(255, 184, 'Paid approved', 'AED '.number_format((float) $invoice->paid_amount, 2), '059669')
            ->totalBox(52, 112, 'Balance due', 'AED '.number_format((float) $invoice->balance_amount, 2), '061A38');

        return $pdf->output();
    }

    public function receipt(Receipt $receipt): string
    {
        $receipt->loadMissing(['invoice', 'booking.tenant', 'booking.unit.building']);

        $pdf = new BrandedPdf('Payment Receipt', $receipt->receipt_no);

        $pdf->labelValue(52, 640, 'Receipt no', $receipt->receipt_no)
            ->labelValue(222, 640, 'Invoice no', $receipt->invoice->invoice_no)
            ->labelValue(392, 640, 'Issued', $receipt->issued_at?->format('M d, Y H:i') ?: now()->format('M d, Y H:i'))
            ->labelValue(52, 580, 'Tenant', $receipt->booking->tenant->full_name)
            ->labelValue(222, 580, 'Booking', $receipt->booking->booking_no)
            ->labelValue(392, 580, 'Unit', $receipt->booking->unit->building->name.' / '.$receipt->booking->unit->unit_no)
            ->totalBox(52, 474, 'Received amount', 'AED '.number_format((float) $receipt->amount, 2), '059669')
            ->rect(52, 380, 500, 70, 'EFF6FF', 'BFDBFE')
            ->text(75, 420, 'Tenant check-in code', 10, 'bold', '2563EB')
            ->text(75, 394, $receipt->check_in_code, 26, 'bold', '071A3B')
            ->text(52, 330, 'Keep this receipt for your records. Check-in code is shared after finance approval.', 10, 'regular', '64748B');

        return $pdf->output();
    }
}
