<?php

namespace App\Mail;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportConversationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public SupportTicket $ticket, public ?SupportMessage $supportMessage = null, public bool $created = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->created ? 'Support request received: ' : 'Support reply: ').$this->ticket->ticket_no);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.support.conversation');
    }
}
