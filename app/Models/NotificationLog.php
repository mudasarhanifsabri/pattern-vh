<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['booking_id', 'channel', 'recipient', 'subject', 'message', 'status', 'payload', 'sent_at'])]
class NotificationLog extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    protected static function booted(): void
    {
        static::created(function (NotificationLog $notification): void {
            if ($notification->channel !== 'push') {
                return;
            }

            app()->terminating(function () use ($notification): void {
                app(\App\Support\WebPushSender::class)->send($notification->fresh() ?: $notification);
            });
        });
    }
}
