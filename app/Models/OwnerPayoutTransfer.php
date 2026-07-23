<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_id', 'payment_id', 'booking_id', 'unit_id', 'gross_share', 'management_fee', 'owner_expenses',
    'net_payout', 'collection_date', 'payable_on', 'transferred_at', 'reference_no', 'notes', 'created_by',
])]
class OwnerPayoutTransfer extends Model
{
    protected function casts(): array
    {
        return [
            'gross_share' => 'decimal:2',
            'management_fee' => 'decimal:2',
            'owner_expenses' => 'decimal:2',
            'net_payout' => 'decimal:2',
            'collection_date' => 'date',
            'payable_on' => 'date',
            'transferred_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
