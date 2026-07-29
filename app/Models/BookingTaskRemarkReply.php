<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_task_remark_id', 'user_id', 'reply'])]
class BookingTaskRemarkReply extends Model
{
    public function remark(): BelongsTo
    {
        return $this->belongsTo(BookingTaskRemark::class, 'booking_task_remark_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
