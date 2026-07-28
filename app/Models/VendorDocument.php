<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'document_type', 'title', 'document_number', 'expiry_date', 'disk', 'path', 'original_name', 'created_by',
])]
class VendorDocument extends Model
{
    public const TYPES = [
        'trade_license', 'tax_registration_certificate', 'insurance_certificate', 'bank_letter',
        'company_profile', 'passport_emirates_id', 'contract', 'other',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
