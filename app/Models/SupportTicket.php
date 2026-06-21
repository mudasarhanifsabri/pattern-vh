<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'ticket_no', 'public_token', 'mode', 'requester_user_id', 'requester_type', 'requester_role',
    'requester_name', 'requester_email', 'requester_mobile', 'support_category_id', 'subject',
    'description', 'priority', 'status', 'channel', 'assigned_to', 'booking_id', 'unit_id',
    'tenant_id', 'owner_id', 'agent_id', 'operations_team_member_id', 'payment_id',
    'first_response_at', 'last_response_at', 'resolved_at', 'closed_at', 'created_by', 'updated_by',
])]
class SupportTicket extends Model
{
    use SoftDeletes;

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const STATUSES = ['open', 'waiting_for_customer', 'in_progress', 'resolved', 'closed'];
    public const MODES = ['chat', 'ticket'];

    protected function casts(): array
    {
        return [
            'first_response_at' => 'datetime', 'last_response_at' => 'datetime',
            'resolved_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(SupportCategory::class, 'support_category_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requester_user_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function owner(): BelongsTo { return $this->belongsTo(Owner::class); }
    public function agent(): BelongsTo { return $this->belongsTo(Agent::class); }
    public function maintainer(): BelongsTo { return $this->belongsTo(OperationsTeamMember::class, 'operations_team_member_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function messages(): HasMany { return $this->hasMany(SupportMessage::class); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }
}
