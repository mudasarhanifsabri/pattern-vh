<?php

namespace App\Mail;

use App\Models\Unit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class UnitAccessCardRequestMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Unit $unit,
        public string $requestType,
        public string $cardType,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        $this->unit->loadMissing('building');

        return new Envelope(
            subject: "{$this->cardType} {$this->requestType} - {$this->unit->building->name} Unit {$this->unit->unit_no}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.units.access-card-request',
        );
    }

    public function attachments(): array
    {
        $this->unit->loadMissing('owners');

        $attachments = [];

        if ($this->unit->title_deed_path) {
            $attachments[] = Attachment::fromStorageDisk($this->unit->title_deed_disk ?? config('filesystems.default'), $this->unit->title_deed_path)
                ->as($this->unit->title_deed_original_name ?: "Title Deed - Unit {$this->unit->unit_no}");
        }

        $owner = $this->primaryOwner();
        if ($owner?->document_path) {
            $safeName = Str::slug($owner->full_name) ?: 'owner';
            $attachments[] = Attachment::fromStorageDisk($owner->document_disk ?? config('filesystems.default'), $owner->document_path)
                ->as($owner->document_original_name ?: "Primary Owner ID - {$safeName}");
        }

        return $attachments;
    }

    private function primaryOwner()
    {
        return $this->unit->owners
            ->sortByDesc(fn ($owner) => (float) ($owner->pivot?->share_percent ?? 0))
            ->first();
    }
}
