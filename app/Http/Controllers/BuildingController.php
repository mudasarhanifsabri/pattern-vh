<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index()
    {
        $buildings = Building::query()
            ->withCount('units')
            ->when(request('search'), fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")->orWhere('area', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('buildings.index', compact('buildings'));
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['security_emails'] = $this->emails($request->string('security_emails')->toString());
        $validated['amenities'] = $this->lines($request->string('amenities')->toString());
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $building = Building::create($validated);

        ActivityLogger::log('buildings.created', "Created building {$building->name}.", $building);

        return redirect()->route('buildings.show', $building)->with('status', 'Building created successfully.');
    }

    public function show(Building $building)
    {
        return view('buildings.show', [
            'building' => $building->load('units.owners'),
        ]);
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $validated = $this->validated($request);
        $validated['security_emails'] = $this->emails($request->string('security_emails')->toString());
        $validated['amenities'] = $this->lines($request->string('amenities')->toString());
        $validated['updated_by'] = auth()->id();

        $building->update($validated);

        ActivityLogger::log('buildings.updated', "Updated building {$building->name}.", $building);

        return redirect()->route('buildings.show', $building)->with('status', 'Building updated successfully.');
    }

    public function destroy(Building $building)
    {
        ActivityLogger::log('buildings.deleted', "Deleted building {$building->name}.", $building);
        $building->delete();

        return redirect()->route('buildings.index')->with('status', 'Building deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:191'],
            'area' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'security_emails' => ['nullable', 'string', 'max:2000'],
            'amenities' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function emails(string $emails): array
    {
        return collect(explode(',', $emails))
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->values()
            ->all();
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
