<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'client_id', 'client_secret', 'username', 'password', 'redirect_uri', 'is_active',
])]
class TtLockSetting extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function locks(): HasMany
    {
        return $this->hasMany(TtLock::class);
    }
}
