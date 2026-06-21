<?php

namespace App\Models;

use App\Models\Concerns\HasPeopleProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'full_name', 'user_id', 'mobile_no', 'mobile_has_whatsapp', 'email', 'identity_type', 'identity_no',
    'identity_expiry_date', 'date_of_birth', 'document_disk', 'document_path', 'document_original_name',
    'is_blacklisted', 'blacklist_reason', 'bank_name', 'bank_account_name', 'bank_account_no', 'iban', 'swift_code',
    'agency_name', 'rera_no', 'commission_percent', 'created_by', 'updated_by', 'portal_invitation_sent_at',
])]
class Agent extends Model
{
    use HasPeopleProfile;
    use SoftDeletes;

    protected function casts(): array
    {
        return array_merge($this->peopleCasts(), [
            'commission_percent' => 'decimal:2',
        ]);
    }
}
