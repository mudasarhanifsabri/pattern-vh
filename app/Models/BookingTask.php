<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'booking_id', 'unit_id', 'assigned_to_id', 'task_type', 'title', 'due_at', 'status',
    'task_number', 'category', 'priority', 'progress', 'accepted_at', 'started_at', 'expected_completion_date',
    'completed_at', 'completion_notes', 'checklist', 'attachments', 'notes', 'description', 'pictures',
    'final_images', 'invoice_attachment', 'receipt_attachment', 'warranty_attachment', 'labor_cost',
    'material_cost', 'other_expenses', 'total_cost',
])]
class BookingTask extends Model
{
    public const STATUSES = ['open', 'assigned', 'accepted', 'in_progress', 'waiting_approval', 'completed', 'closed', 'blocked', 'cancelled'];

    public const TYPES = [
        'checkout_cleaning' => 'Checkout Cleaning',
        'checkout_inspection' => 'Checkout Inspection',
        'maintenance' => 'Maintenance',
        'cleaning' => 'Cleaning',
        'tenant_checkin_review' => 'Tenant Check-in Review',
        'other' => 'Other',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'expected_completion_date' => 'date',
            'completed_at' => 'datetime',
            'checklist' => 'array',
            'attachments' => 'array',
            'pictures' => 'array',
            'final_images' => 'array',
            'labor_cost' => 'decimal:2',
            'material_cost' => 'decimal:2',
            'other_expenses' => 'decimal:2',
            'total_cost' => 'decimal:2',
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

    public function remarks(): HasMany
    {
        return $this->hasMany(BookingTaskRemark::class)->latest();
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(BookingTaskCostItem::class);
    }

    public function getTaskDisplayNumberAttribute(): string
    {
        return $this->task_number ?: 'TSK-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->task_type] ?? str($this->task_type)->replace('_', ' ')->headline();
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? str($this->priority)->headline();
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_overdue) {
            return 'Overdue';
        }

        return str($this->status)->replace('_', ' ')->headline();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at && $this->due_at->isPast() && ! in_array($this->status, ['completed', 'closed', 'cancelled'], true);
    }

    public function recalculateCosts(): void
    {
        $labor = (float) $this->costItems()->where('type', 'labor')->sum('amount');
        $materials = (float) $this->costItems()->where('type', 'material')->sum('amount');
        $other = (float) $this->costItems()->where('type', 'other')->sum('amount');

        $this->update([
            'labor_cost' => $labor,
            'material_cost' => $materials,
            'other_expenses' => $other,
            'total_cost' => $labor + $materials + $other,
        ]);
    }
}
