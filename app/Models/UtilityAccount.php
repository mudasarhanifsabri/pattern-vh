<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'unit_id', 'provider_type', 'provider_name', 'account_no', 'username', 'password',
    'paid_by_company', 'billing_day', 'next_due_date', 'estimated_amount', 'status',
    'notes', 'created_by', 'updated_by',
])]
class UtilityAccount extends Model
{
    use SoftDeletes;

    public const PROVIDER_TYPES = ['dewa', 'gas', 'internet', 'cooling', 'other'];

    protected function casts(): array
    {
        return [
            'paid_by_company' => 'boolean',
            'billing_day' => 'integer',
            'next_due_date' => 'date',
            'estimated_amount' => 'decimal:2',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(UtilityBill::class);
    }
}
