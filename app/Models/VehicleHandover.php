<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'vehicle_id', 'team_member_id', 'handover_type', 'handover_at', 'odometer',
    'fuel_level', 'photos', 'remarks', 'created_by',
])]
class VehicleHandover extends Model
{
    use SoftDeletes;

    public const TYPES = ['check_out', 'check_in'];

    protected function casts(): array
    {
        return [
            'handover_at' => 'datetime',
            'odometer' => 'integer',
            'photos' => 'array',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(OperationsTeamMember::class, 'team_member_id');
    }
}
