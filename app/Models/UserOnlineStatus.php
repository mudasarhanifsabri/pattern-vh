<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'is_online', 'last_seen_at', 'status_text'])]
class UserOnlineStatus extends Model
{
    protected function casts(): array { return ['is_online' => 'boolean', 'last_seen_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
