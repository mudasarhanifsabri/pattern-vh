<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'contract_no', 'owner_id', 'unit_id', 'status', 'signing_token', 'contract_document_disk',
    'contract_document_path', 'contract_document_original_name', 'contract_start_date', 'contract_end_date',
    'effective_date', 'company_name', 'company_registration_no', 'company_contact_no',
    'company_email', 'company_address', 'owner_name', 'owner_nationality', 'owner_passport_no',
    'owner_contact_no', 'owner_email', 'property_name', 'floor_no', 'community', 'property_no',
    'property_type', 'dewa_account_no', 'management_fee_percent', 'startup_fee', 'furniture_fee',
    'vat_amount', 'grand_total', 'bank_account_holder', 'bank_currency', 'bank_name',
    'bank_account_no', 'iban', 'swift_code', 'special_terms', 'company_signed_at',
    'company_signature_name', 'company_signature_data', 'company_signed_ip',
    'owner_signed_at', 'owner_signature_name', 'owner_signature_data', 'owner_signed_ip',
    'owner_signed_user_agent', 'signed_document_disk', 'signed_document_path', 'signed_document_original_name',
    'signature_link_emailed_at', 'created_by', 'updated_by',
])]
class OwnerUnitContract extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'sent', 'active', 'expired', 'terminated'];

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'effective_date' => 'date',
            'management_fee_percent' => 'decimal:2',
            'startup_fee' => 'decimal:2',
            'furniture_fee' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'company_signed_at' => 'datetime',
            'owner_signed_at' => 'datetime',
            'signature_link_emailed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
