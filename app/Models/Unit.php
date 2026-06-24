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
    'building_id', 'unit_no', 'unit_type', 'availability_status', 'floor', 'bedrooms', 'bathrooms', 'size_sqft', 'view',
    'parking_no', 'wifi_name', 'wifi_password', 'management_fee_percent', 'rent_period', 'rent_amount', 'amenities',
    'pictures', 'internet_provider', 'internet_account_no', 'electricity_company', 'electricity_paid_by_us',
    'electricity_username', 'electricity_password', 'gas_company', 'gas_details', 'hvac_details', 'other_utility_details',
    'title_deed_no', 'title_deed_issue_date', 'title_deed_expiry_date', 'title_deed_disk', 'title_deed_path', 'title_deed_original_name',
    'dtcm_permit_no', 'dtcm_permit_expiry_date', 'dtcm_permit_disk', 'dtcm_permit_path', 'dtcm_permit_original_name',
    'ttlock_settings', 'ttlock_locks', 'tt_lock_id', 'ttlock_attachment_disk', 'ttlock_attachment_path', 'ttlock_attachment_original_name', 'notes',
    'created_by', 'updated_by',
])]
class Unit extends Model
{
    use SoftDeletes;

    public const TYPES = ['Studio', '1 BHK', '2 BHK', '3 BHK', '4 BHK', '5 BHK', 'Villa', 'Penthouse'];

    public const AVAILABILITY_STATUSES = ['available', 'occupied', 'maintenance', 'blocked'];

    public const RENT_PERIODS = ['monthly', 'seasonal', 'yearly'];

    protected function casts(): array
    {
        return [
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'size_sqft' => 'decimal:2',
            'management_fee_percent' => 'decimal:2',
            'rent_amount' => 'decimal:2',
            'amenities' => 'array',
            'pictures' => 'array',
            'electricity_paid_by_us' => 'boolean',
            'title_deed_issue_date' => 'date',
            'title_deed_expiry_date' => 'date',
            'dtcm_permit_expiry_date' => 'date',
            'ttlock_settings' => 'array',
            'ttlock_locks' => 'array',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class)->withPivot('share_percent')->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function utilityAccounts(): HasMany
    {
        return $this->hasMany(UtilityAccount::class);
    }

    public function ownerContracts(): HasMany
    {
        return $this->hasMany(OwnerUnitContract::class);
    }

    public function ttLock(): BelongsTo
    {
        return $this->belongsTo(TtLock::class, 'tt_lock_id');
    }

    public function ttLockEvents(): HasMany
    {
        return $this->hasMany(TtLockEvent::class);
    }

    public function documentUrl(string $type): ?string
    {
        $path = $this->getAttribute("{$type}_path");
        $disk = $this->getAttribute("{$type}_disk") ?? config('filesystems.default');

        return $path ? Storage::disk($disk)->url($path) : null;
    }
}
