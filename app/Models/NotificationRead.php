<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['notification_log_id', 'user_id', 'read_at'])]
class NotificationRead extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'notification_log_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
