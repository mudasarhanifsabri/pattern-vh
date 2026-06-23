<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['bank_account_id', 'bank_statement_import_id', 'transaction_date', 'type', 'amount', 'balance', 'reference_no', 'description', 'status', 'matched_type', 'matched_id', 'matched_at', 'matched_by', 'fingerprint'])]
class BankTransaction extends Model
{
    public const STATUSES = ['unmatched', 'suggested', 'matched', 'ignored'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2', 'balance' => 'decimal:2', 'matched_at' => 'datetime'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankTransactionMatch::class);
    }

    public function matched(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'matched_type', 'matched_id');
    }
}
