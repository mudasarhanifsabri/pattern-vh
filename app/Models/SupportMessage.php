<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'support_ticket_id', 'user_id', 'sender_type', 'sender_name', 'body', 'is_internal_note',
    'is_auto_reply', 'delivery_status', 'whatsapp_template', 'emailed_at', 'read_at',
])]
class SupportMessage extends Model
{
    protected function casts(): array
    {
        return [
            'is_internal_note' => 'boolean', 'is_auto_reply' => 'boolean',
            'emailed_at' => 'datetime', 'read_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }
}
