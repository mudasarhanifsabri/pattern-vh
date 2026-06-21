<?php

namespace App\Mail;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReceiptIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Receipt $receipt) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Receipt {$this->receipt->receipt_no} and check-in code");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.receipts.issued');
    }
}
