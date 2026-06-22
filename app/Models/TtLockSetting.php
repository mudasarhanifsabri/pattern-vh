<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'client_id', 'client_secret', 'username', 'password', 'redirect_uri', 'is_active',
    'access_token', 'refresh_token', 'token_expires_at', 'last_tested_at', 'last_error',
])]
class TtLockSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'token_expires_at' => 'datetime',
            'last_tested_at' => 'datetime',
        ];
    }

    public function locks(): HasMany
    {
        return $this->hasMany(TtLock::class);
    }
}
