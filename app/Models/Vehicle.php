<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'plate_no', 'vehicle_type', 'make_model', 'status', 'odometer',
    'registration_expiry_date', 'insurance_expiry_date', 'notes', 'created_by', 'updated_by',
])]
class Vehicle extends Model
{
    use SoftDeletes;

    public const STATUSES = ['available', 'checked_out', 'maintenance', 'inactive'];

    protected function casts(): array
    {
        return [
            'odometer' => 'integer',
            'registration_expiry_date' => 'date',
            'insurance_expiry_date' => 'date',
        ];
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(VehicleHandover::class);
    }
}
