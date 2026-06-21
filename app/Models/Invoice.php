<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_no', 'booking_id', 'tenant_id', 'unit_id', 'invoice_date', 'due_date', 'rent_amount',
    'period_start', 'period_end', 'period_index', 'is_initial_invoice',
    'deposit_amount', 'dtcm_fee', 'cleaning_fee', 'agency_fee', 'vat_amount', 'total_amount', 'paid_amount',
    'balance_amount', 'status', 'notes', 'sent_at', 'created_by', 'updated_by',
])]
class Invoice extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'sent', 'partially_paid', 'paid', 'cancelled'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'period_index' => 'integer',
            'is_initial_invoice' => 'boolean',
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'dtcm_fee' => 'decimal:2',
            'cleaning_fee' => 'decimal:2',
            'agency_fee' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function unit(): BelongsTo { return $this->belongsTo(Unit::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function receipts(): HasMany { return $this->hasMany(Receipt::class); }
    public function collectionRequests(): HasMany { return $this->hasMany(PaymentCollectionRequest::class); }
    public function extensionRequest() { return $this->hasOne(BookingExtensionRequest::class); }
}
