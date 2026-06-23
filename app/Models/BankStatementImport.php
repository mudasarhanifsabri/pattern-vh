<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bank_account_id', 'original_name', 'statement_from', 'statement_to', 'rows_total', 'rows_imported', 'rows_duplicate', 'status', 'notes', 'created_by'])]
class BankStatementImport extends Model
{
    protected function casts(): array
    {
        return ['statement_from' => 'date', 'statement_to' => 'date'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
