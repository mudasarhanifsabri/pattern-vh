<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['bank_transaction_id', 'matchable_type', 'matchable_id', 'confidence', 'status', 'reason', 'confirmed_by', 'confirmed_at'])]
class BankTransactionMatch extends Model
{
    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'confidence' => 'integer'];
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function matchable(): MorphTo
    {
        return $this->morphTo();
    }
}
