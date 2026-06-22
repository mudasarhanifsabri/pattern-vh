<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tt_lock_id', 'unit_id', 'lock_id', 'lock_name', 'event_type', 'operator_name',
    'keyboard_pwd', 'record_id', 'event_at', 'source', 'payload',
])]
class TtLockEvent extends Model
{
    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function ttLock(): BelongsTo
    {
        return $this->belongsTo(TtLock::class, 'tt_lock_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
