<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'expense_no', 'name', 'type', 'expense_to_role', 'expense_to_id', 'owner_id', 'unit_id', 'association',
    'incurred_on', 'amount', 'notes', 'receipt_disk', 'receipt_path', 'receipt_original_name', 'created_by', 'updated_by',
])]
class Expense extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'maintenance',
        'cleaning',
        'utilities',
        'internet',
        'guest_supplies',
        'repair',
        'management',
        'commission',
        'government_fee',
        'other',
    ];

    public const TARGET_ROLES = ['company', 'owner', 'tenant', 'agent', 'operations_team'];

    public const ASSOCIATIONS = ['company', 'owner_account', 'unit', 'booking', 'operations'];

    protected function casts(): array
    {
        return [
            'incurred_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
