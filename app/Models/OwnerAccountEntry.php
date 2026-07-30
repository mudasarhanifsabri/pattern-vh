<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'owner_id', 'unit_id', 'entry_date', 'type', 'direction', 'amount', 'reference_no', 'description', 'notes', 'created_by',
])]
class OwnerAccountEntry extends Model
{
    public const TYPES = [
        'rent_income' => 'Rent income',
        'payment_received' => 'Payment received',
        'bank_transfer' => 'Bank transfer',
        'expense' => 'Expense',
        'management_fee' => 'Management fee',
        'refund' => 'Refund',
        'opening_balance' => 'Opening balance',
        'adjustment' => 'Adjustment',
        'other' => 'Other',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
