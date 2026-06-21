<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id', 'tenant_id', 'invoice_id', 'requested_check_out_date', 'extra_rent_amount', 'status',
    'tenant_notes', 'approval_notes', 'approved_by', 'approved_at',
])]
class BookingExtensionRequest extends Model
{
    public const STATUSES = ['requested', 'approved_pending_payment', 'paid_extended', 'rejected'];

    protected function casts(): array
    {
        return [
            'requested_check_out_date' => 'date',
            'extra_rent_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
}
