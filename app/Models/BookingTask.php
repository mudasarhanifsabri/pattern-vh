<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_id', 'unit_id', 'assigned_to_id', 'task_type', 'title', 'due_at', 'status',
    'priority', 'started_at', 'completed_at', 'completion_notes', 'checklist', 'attachments', 'notes',
])]
class BookingTask extends Model
{
    public const STATUSES = ['open', 'in_progress', 'completed', 'blocked', 'cancelled'];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'checklist' => 'array',
            'attachments' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(OperationsTeamMember::class, 'assigned_to_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BookingTaskEvent::class)->latest();
    }
}
