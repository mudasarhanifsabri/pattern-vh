<?php

namespace App\Mail;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OwnerExpenseRecordedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Expense $expense) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New expense {$this->expense->expense_no} added to your property");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.owners.expense-recorded');
    }
}
