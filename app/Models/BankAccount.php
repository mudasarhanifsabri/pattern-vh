<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'bank_name', 'account_no', 'iban', 'currency', 'is_active'])]
class BankAccount extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
