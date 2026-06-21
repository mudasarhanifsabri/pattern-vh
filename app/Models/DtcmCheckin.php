<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'status', 'portal_reference', 'submitted_at', 'notes'])]
class DtcmCheckin extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
