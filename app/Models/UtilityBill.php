<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'utility_account_id', 'bill_date', 'due_date', 'amount', 'status', 'receipt_disk',
    'receipt_path', 'receipt_original_name', 'notes', 'created_by', 'updated_by',
])]
class UtilityBill extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'paid', 'overdue', 'disputed'];

    protected function casts(): array
    {
        return [
            'bill_date' => 'date',
            'due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function utilityAccount(): BelongsTo
    {
        return $this->belongsTo(UtilityAccount::class);
    }
}
