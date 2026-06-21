<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id', 'tenant_id', 'deposit_amount', 'damage_amount', 'refund_amount', 'status',
    'inspection_notes', 'damage_report', 'inspection_completed_at', 'tenant_accepted_at',
    'refund_processed_at', 'processed_by',
])]
class BookingDepositRefund extends Model
{
    public const STATUSES = ['pending_inspection', 'tenant_review', 'accepted', 'refund_processing', 'refunded'];

    protected function casts(): array
    {
        return [
            'deposit_amount' => 'decimal:2',
            'damage_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'inspection_completed_at' => 'datetime',
            'tenant_accepted_at' => 'datetime',
            'refund_processed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
