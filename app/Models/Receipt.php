<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'payment_id', 'booking_id', 'receipt_no', 'check_in_code', 'amount', 'issued_at', 'emailed_at'])]
class Receipt extends Model
{
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issued_at' => 'datetime', 'emailed_at' => 'datetime'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
