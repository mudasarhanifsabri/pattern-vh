<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

class BookingConfirmationPdf
{
    public function make(Booking $booking): string
    {
        ini_set('pcre.backtrack_limit', '10000000');

        $booking->loadMissing([
            'unit.building',
            'tenant',
            'agent',
            'invoices.payments',
        ]);

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);

        $pdf->SetTitle($booking->booking_no.' Booking Confirmation');
        $pdf->WriteHTML(view('pdfs.booking-confirmation', [
            'booking' => $booking,
            'logo' => $this->fileImageData(public_path('brand/pattern-logo.jpeg')),
            'propertyImage' => $this->unitImageData($booking),
            'chargeRows' => $this->chargeRows($booking),
            'paymentSummary' => $this->paymentSummary($booking),
            'stayNights' => $this->stayNights($booking),
        ])->render());

        return $pdf->Output('', 'S');
    }

    private function chargeRows(Booking $booking): array
    {
        return collect([
            ['Accommodation charges', $this->stayNights($booking).' days', (float) $booking->rent_amount],
            ['VAT 5% on rent', '-', (float) $booking->vat_amount],
            ['Security deposit', '1', (float) $booking->deposit_amount],
            ['DTCM fee', '1', (float) $booking->dtcm_fee],
            ['Cleaning fee', '1', (float) $booking->cleaning_fee],
            ['Agency fee', '1', (float) $booking->agency_fee],
        ])->filter(fn (array $row): bool => $row[2] > 0)->values()->all();
    }

    private function paymentSummary(Booking $booking): array
    {
        $invoices = $booking->invoices;
        $approvedPaid = $invoices
            ->flatMap(fn ($invoice) => $invoice->payments)
            ->where('status', 'approved')
            ->sum(fn ($payment) => (float) $payment->amount);

        $invoiced = $invoices->sum(fn ($invoice) => (float) $invoice->total_amount);
        $balance = $invoices->sum(fn ($invoice) => (float) $invoice->balance_amount);
        $latestPayment = $invoices
            ->flatMap(fn ($invoice) => $invoice->payments)
            ->sortByDesc('paid_at')
            ->first();

        return [
            'status' => $balance <= 0 && $approvedPaid > 0 ? 'Paid' : ($approvedPaid > 0 ? 'Partially paid' : 'Pending'),
            'paid' => $approvedPaid,
            'balance' => max(0, $balance ?: ((float) $booking->total_amount - $approvedPaid)),
            'invoiced' => $invoiced ?: (float) $booking->total_amount,
            'method' => $latestPayment ? str($latestPayment->method)->replace('_', ' ')->headline()->toString() : 'Not recorded',
            'reference' => $latestPayment?->reference_no ?: $latestPayment?->payment_no ?: 'Not recorded',
            'date' => $latestPayment?->paid_at?->format('M d, Y') ?: 'Not recorded',
        ];
    }

    private function stayNights(Booking $booking): int
    {
        if (! $booking->check_in_date || ! $booking->check_out_date) {
            return 0;
        }

        return max(1, $booking->check_in_date->diffInDays($booking->check_out_date));
    }

    private function unitImageData(Booking $booking): ?string
    {
        $picture = collect($booking->unit?->pictures ?? [])->first();
        if (! $picture || empty($picture['path'])) {
            return null;
        }

        $disk = Storage::disk($picture['disk'] ?? config('filesystems.default'));

        try {
            if (! $disk->exists($picture['path'])) {
                return null;
            }

            $contents = $disk->get($picture['path']);
        } catch (\Throwable) {
            return null;
        }

        $mime = $picture['mime'] ?? $this->mimeFromName($picture['name'] ?? $picture['path']);

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function fileImageData(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        return 'data:'.$this->mimeFromName($path).';base64,'.base64_encode(file_get_contents($path));
    }

    private function mimeFromName(string $name): string
    {
        return match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
