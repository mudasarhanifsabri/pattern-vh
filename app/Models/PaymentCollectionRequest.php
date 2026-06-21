<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id', 'booking_id', 'tenant_id', 'requested_by', 'request_no', 'collection_method', 'amount',
    'preferred_date', 'preferred_time_window', 'contact_mobile', 'contact_has_whatsapp', 'collection_address',
    'status', 'tenant_notes', 'office_notes', 'assigned_to_id', 'scheduled_at', 'collected_at', 'payment_id',
    'created_by', 'updated_by',
])]
class PaymentCollectionRequest extends Model
{
    public const METHODS = ['cash', 'card_machine'];

    public const STATUSES = ['requested', 'scheduled', 'collected_pending_verification', 'approved', 'rejected', 'cancelled'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'preferred_date' => 'date',
            'contact_has_whatsapp' => 'boolean',
            'scheduled_at' => 'datetime',
            'collected_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(OperationsTeamMember::class, 'assigned_to_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
