<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_task_id', 'type', 'label', 'worker', 'hours', 'rate', 'quantity', 'unit_price', 'amount'])]
class BookingTaskCostItem extends Model
{
    protected function casts(): array
    {
        return [
            'hours' => 'decimal:2',
            'rate' => 'decimal:2',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(BookingTask::class, 'booking_task_id');
    }
}
