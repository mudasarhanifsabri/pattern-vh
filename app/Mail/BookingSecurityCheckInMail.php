<?php

namespace App\Mail;

use App\Models\Booking;
use App\Support\BookingConfirmationPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSecurityCheckInMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $this->booking->loadMissing(['unit.building', 'tenant']);

        return new Envelope(
            subject: "Check-in details - {$this->booking->unit->building->name} Unit {$this->booking->unit->unit_no} - {$this->booking->tenant->full_name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bookings.security-check-in');
    }

    public function attachments(): array
    {
        $this->booking->loadMissing(['unit', 'tenant']);

        $attachments = [
            Attachment::fromData(fn () => app(BookingConfirmationPdf::class)->make($this->booking), "{$this->booking->booking_no}-confirmation.pdf")
                ->withMime('application/pdf'),
        ];

        if ($this->booking->tenant->document_path) {
            $attachments[] = Attachment::fromStorageDisk($this->booking->tenant->document_disk ?? config('filesystems.default'), $this->booking->tenant->document_path)
                ->as($this->booking->tenant->document_original_name ?: "Tenant ID - {$this->booking->tenant->full_name}");
        }

        if ($this->booking->unit->dtcm_permit_path) {
            $attachments[] = Attachment::fromStorageDisk($this->booking->unit->dtcm_permit_disk ?? config('filesystems.default'), $this->booking->unit->dtcm_permit_path)
                ->as($this->booking->unit->dtcm_permit_original_name ?: "DTCM Permit - Unit {$this->booking->unit->unit_no}");
        }

        return $attachments;
    }
}
