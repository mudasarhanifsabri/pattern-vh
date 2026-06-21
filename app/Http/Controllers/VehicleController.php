<?php

namespace App\Http\Controllers;

use App\Models\OperationsTeamMember;
use App\Models\Vehicle;
use App\Models\VehicleHandover;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index()
    {
        return view('vehicles.index', [
            'vehicles' => Vehicle::with(['handovers.teamMember'])->latest()->get(),
            'teamMembers' => OperationsTeamMember::orderBy('full_name')->get(),
            'statuses' => Vehicle::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'plate_no' => ['required', 'string', 'max:50', 'unique:vehicles,plate_no'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'make_model' => ['nullable', 'string', 'max:191'],
            'status' => ['required', Rule::in(Vehicle::STATUSES)],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'registration_expiry_date' => ['nullable', 'date'],
            'insurance_expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $vehicle = Vehicle::create($validated);
        ActivityLogger::log('vehicles.created', "Created vehicle {$vehicle->plate_no}.", $vehicle);

        return redirect()->route('vehicles.index')->with('status', 'Vehicle added.');
    }

    public function handover(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'team_member_id' => ['nullable', 'exists:operations_team_members,id'],
            'handover_type' => ['required', Rule::in(VehicleHandover::TYPES)],
            'handover_at' => ['required', 'date'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'fuel_level' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'front_photo' => ['nullable', 'image', 'max:5120'],
            'back_photo' => ['nullable', 'image', 'max:5120'],
            'left_photo' => ['nullable', 'image', 'max:5120'],
            'right_photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $validated['photos'] = $this->storePhotos($request, $vehicle, $validated['handover_type']);
        $validated['created_by'] = $request->user()->id;

        $handover = $vehicle->handovers()->create($validated);
        $vehicle->update([
            'status' => $validated['handover_type'] === 'check_out' ? 'checked_out' : 'available',
            'odometer' => $validated['odometer'] ?? $vehicle->odometer,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log('vehicles.handover', "Recorded {$validated['handover_type']} for {$vehicle->plate_no}.", $handover);

        return redirect()->route('vehicles.index')->with('status', 'Vehicle handover saved.');
    }

    private function storePhotos(Request $request, Vehicle $vehicle, string $type): array
    {
        $photos = [];
        $disk = config('filesystems.default');

        foreach (['front_photo' => 'Front', 'back_photo' => 'Back', 'left_photo' => 'Left', 'right_photo' => 'Right'] as $field => $label) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $name = "{$label} - {$type} - {$vehicle->plate_no}.{$file->getClientOriginalExtension()}";
            $path = ErpStoragePath::documentPath('Vehicles', $vehicle->plate_no, 'handover-photos', $file, $name);
            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
            $photos[$field] = ['disk' => $disk, 'path' => $path, 'name' => $name];
        }

        return $photos;
    }
}
