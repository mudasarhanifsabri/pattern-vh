<?php

namespace App\Models;

use App\Models\Concerns\HasPeopleProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'full_name', 'user_id', 'mobile_no', 'mobile_has_whatsapp', 'email', 'identity_type', 'identity_no',
    'identity_expiry_date', 'date_of_birth', 'document_disk', 'document_path', 'document_original_name',
    'is_blacklisted', 'blacklist_reason', 'bank_name', 'bank_account_name', 'bank_account_no', 'iban', 'swift_code',
    'emergency_contact_name', 'emergency_contact_mobile', 'nationality', 'created_by', 'updated_by',
    'portal_invitation_sent_at',
])]
class Tenant extends Model
{
    use HasPeopleProfile;
    use SoftDeletes;

    protected function casts(): array
    {
        return $this->peopleCasts();
    }

    public function collectionRequests(): HasMany
    {
        return $this->hasMany(PaymentCollectionRequest::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
