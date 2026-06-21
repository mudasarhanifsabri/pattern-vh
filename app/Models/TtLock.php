<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'tt_lock_setting_id', 'lock_name', 'lock_id', 'lock_alias', 'gateway_id', 'mac_address',
    'battery_level', 'signal_strength', 'status', 'last_synced_at', 'notes',
])]
class TtLock extends Model
{
    public const STATUSES = ['active', 'inactive', 'maintenance', 'owner_managed'];

    protected function casts(): array
    {
        return [
            'battery_level' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(TtLockSetting::class, 'tt_lock_setting_id');
    }

    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class, 'tt_lock_id');
    }
}
