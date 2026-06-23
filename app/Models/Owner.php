<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'full_name',
    'user_id',
    'mobile_no',
    'mobile_has_whatsapp',
    'email',
    'identity_type',
    'identity_no',
    'identity_issue_date',
    'identity_expiry_date',
    'date_of_birth',
    'nationality',
    'document_disk',
    'document_path',
    'document_original_name',
    'is_blacklisted',
    'blacklist_reason',
    'bank_name',
    'bank_account_name',
    'bank_account_no',
    'iban',
    'swift_code',
    'created_by',
    'updated_by',
    'portal_invitation_sent_at',
])]
class Owner extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'mobile_has_whatsapp' => 'boolean',
            'identity_issue_date' => 'date',
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

    public function notes(): HasMany
    {
        return $this->hasMany(OwnerNote::class)->latest();
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withPivot('share_percent')->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function unitContracts(): HasMany
    {
        return $this->hasMany(OwnerUnitContract::class);
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
