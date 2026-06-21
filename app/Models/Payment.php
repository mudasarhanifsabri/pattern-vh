<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'invoice_id', 'booking_id', 'collection_request_id', 'payment_no', 'method', 'status', 'amount', 'paid_at', 'reference_no', 'notes',
    'proof_disk', 'proof_path', 'proof_original_name', 'created_by', 'approved_by', 'approved_at', 'verification_notes',
])]
class Payment extends Model
{
    public const METHODS = ['cash', 'bank_transfer', 'card_machine', 'stripe_placeholder'];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function collectionRequest(): BelongsTo { return $this->belongsTo(PaymentCollectionRequest::class); }
    public function receipt(): HasOne { return $this->hasOne(Receipt::class); }
}
