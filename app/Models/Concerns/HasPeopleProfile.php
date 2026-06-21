<?php

namespace App\Models\Concerns;

use App\Models\PersonNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasPeopleProfile
{
    protected function peopleCasts(): array
    {
        return [
            'mobile_has_whatsapp' => 'boolean',
            'identity_expiry_date' => 'date',
            'date_of_birth' => 'date',
            'is_blacklisted' => 'boolean',
            'portal_invitation_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(PersonNote::class, 'notable')->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentUrl(): ?string
    {
        if (! $this->document_path) {
            return null;
        }

        return Storage::disk($this->document_disk ?? config('filesystems.default'))->url($this->document_path);
    }
}
