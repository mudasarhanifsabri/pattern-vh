<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'booking_no', 'booking_type', 'unit_id', 'tenant_id', 'agent_id', 'check_in_date', 'check_out_date',
    'check_in_time', 'check_out_time', 'guest_count', 'rent_amount', 'deposit_amount', 'dtcm_fee',
    'cleaning_fee', 'agency_fee', 'vat_amount', 'rental_periods', 'total_amount', 'booking_status', 'source', 'notes',
    'smart_lock_code_mode', 'smart_lock_code', 'smart_lock_code_valid_from', 'smart_lock_code_valid_until',
    'smart_lock_code_generated_at', 'smart_lock_keyboard_pwd_id', 'smart_lock_code_changed_by_tenant_at',
    'smart_lock_code_note',
    'confirmation_sent_at', 'confirmation_token', 'confirmation_delivery_channels',
    'confirmation_link_sent_at', 'confirmation_signed_at', 'confirmation_signed_by',
    'confirmation_signature_text', 'confirmation_signature_data', 'confirmation_signature_mime',
    'confirmation_signed_ip', 'confirmation_signed_user_agent', 'created_by', 'updated_by',
])]
class Booking extends Model
{
    use SoftDeletes;

    public const TYPES = ['holiday_home', 'long_term'];

    public const STATUSES = ['draft', 'confirmed', 'checked_in', 'checkout_requested', 'checked_out', 'cancelled'];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'dtcm_fee' => 'decimal:2',
            'cleaning_fee' => 'decimal:2',
            'agency_fee' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'rental_periods' => 'array',
            'total_amount' => 'decimal:2',
            'smart_lock_code_valid_from' => 'datetime',
            'smart_lock_code_valid_until' => 'datetime',
            'smart_lock_code_generated_at' => 'datetime',
            'smart_lock_code_changed_by_tenant_at' => 'datetime',
            'confirmation_sent_at' => 'datetime',
            'confirmation_delivery_channels' => 'array',
            'confirmation_link_sent_at' => 'datetime',
            'confirmation_signed_at' => 'datetime',
        ];
    }

    public function getConfirmationSignatureStatusAttribute(): string
    {
        return $this->confirmation_signed_at ? 'signed' : 'not_signed';
    }

    public function getDepositReceiptRecordAttribute(): ?Receipt
    {
        $invoices = $this->relationLoaded('invoices')
            ? $this->invoices
            : $this->invoices()->with('receipts')->get();

        return $invoices
            ->filter(fn (Invoice $invoice) => (float) $invoice->deposit_amount > 0)
            ->flatMap(fn (Invoice $invoice) => $invoice->receipts)
            ->sortByDesc(fn (Receipt $receipt) => $receipt->issued_at?->timestamp ?? $receipt->created_at?->timestamp ?? 0)
            ->first();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(BookingTask::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function dtcmCheckin()
    {
        return $this->hasOne(DtcmCheckin::class);
    }

    public function checkInInspectionItems(): HasMany
    {
        return $this->hasMany(CheckInInspectionItem::class);
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(BookingExtensionRequest::class);
    }

    public function depositRefund()
    {
        return $this->hasOne(BookingDepositRefund::class);
    }
}
