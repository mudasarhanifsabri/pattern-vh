<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'supplier_no', 'company_name', 'legal_name', 'contact_person', 'mobile_no', 'email', 'category',
    'trade_license_no', 'trade_license_expiry_date', 'tax_registration_no', 'address', 'bank_name',
    'bank_account_name', 'iban', 'payment_terms', 'status', 'notes', 'created_by', 'updated_by',
])]
class Vendor extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'cleaning', 'maintenance', 'linen', 'amenities', 'furnishing', 'appliances', 'security',
        'transport', 'professional_services', 'utilities', 'other',
    ];

    public const STATUSES = ['active', 'pending_review', 'inactive'];

    protected function casts(): array
    {
        return [
            'trade_license_expiry_date' => 'date',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }
}
