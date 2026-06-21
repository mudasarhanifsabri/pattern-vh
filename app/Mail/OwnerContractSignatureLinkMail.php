<?php

namespace App\Mail;

use App\Models\OwnerUnitContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerContractSignatureLinkMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public OwnerUnitContract $contract,
        public string $signatureLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Owner contract {$this->contract->contract_no} ready for signature");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owner-contracts.signature-link');
    }
}
