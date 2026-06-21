<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['support_ticket_id', 'support_message_id', 'disk', 'path', 'original_name', 'mime_type', 'size'])]
class TicketAttachment extends Model
{
    public function ticket(): BelongsTo { return $this->belongsTo(SupportTicket::class, 'support_ticket_id'); }
    public function message(): BelongsTo { return $this->belongsTo(SupportMessage::class, 'support_message_id'); }
}
