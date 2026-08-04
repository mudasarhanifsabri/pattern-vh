<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStayReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public int $days) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->days}-day checkout reminder - Extend or confirm checkout");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.bookings.stay-reminder');
    }
}
