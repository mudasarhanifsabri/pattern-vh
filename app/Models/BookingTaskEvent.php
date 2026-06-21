<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_task_id', 'user_id', 'event_type', 'description', 'payload'])]
class BookingTaskEvent extends Model
{
    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(BookingTask::class, 'booking_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
