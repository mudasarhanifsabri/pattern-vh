<?php

namespace App\Http\Controllers;

use App\Mail\UnitAccessCardRequestMail;
use App\Models\Building;
use App\Models\Owner;
use App\Models\TtLock;
use App\Models\Unit;
use App\Models\UtilityAccount;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitController extends Controller
{
    public function index()
    {
        $owner = $this->currentOwner();

        $units = Unit::query()
            ->with(['building', 'owners'])
            ->withCount('bookings')
            ->when($owner, fn ($query) => $query->whereHas('owners', fn ($owners) => $owners->whereKey($owner->id)))
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('unit_no', 'like', "%{$search}%")
                        ->orWhere('unit_type', 'like', "%{$search}%")
                        ->orWhereHas('building', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(request('status'), fn ($query, string $status) => $query->where('availability_status', $status))
            ->when(request('type'), fn ($query, string $type) => $query->where('unit_type', $type))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statsQuery = Unit::query()
            ->when($owner, fn ($query) => $query->whereHas('owners', fn ($owners) => $owners->whereKey($owner->id)));

        return view('units.index', [
            'units' => $units,
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'available' => (clone $statsQuery)->where('availability_status', 'available')->count(),
                'occupied' => (clone $statsQuery)->where('availability_status', 'occupied')->count(),
                'maintenance' => (clone $statsQuery)->where('availability_status', 'maintenance')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('units.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated = $this->normalize($request, $validated);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $validated = array_merge($validated, $this->uploadedDocuments($request));
        unset($validated['owners'], $validated['owner_shares'], $validated['ownership_rows'], $validated['utility_accounts'], $validated['title_deed'], $validated['dtcm_permit'], $validated['ttlock_attachment'], $validated['pictures_upload']);

        $unit = Unit::create($validated);
        $this->syncOwners($unit, $request);
        $this->syncUtilityAccounts($unit, $request);

        ActivityLogger::log('units.created', "Created unit {$unit->unit_no}.", $unit);

        return redirect()->route('units.show', $unit)->with('status', 'Unit created successfully.');
    }

    public function show(Unit $unit)
    {
        $this->authorizeOwnerUnit($unit);

        return view('units.show', [
            'unit' => $unit->load([
                'building',
                'owners',
                'ttLock.setting',
                'utilityAccounts',
                'ownerContracts.owner',
                'bookings' => fn ($query) => $query->with('tenant')->latest('check_in_date'),
            ]),
        ]);
    }

    public function document(Unit $unit, string $type)
    {
        $this->authorizeOwnerUnit($unit);

        abort_unless(in_array($type, ['title_deed', 'dtcm_permit', 'ttlock_attachment'], true), 404);

        $path = $unit->getAttribute("{$type}_path");
        abort_if(! $path, 404);

        $disk = Storage::disk($unit->getAttribute("{$type}_disk") ?? config('filesystems.default'));

        $name = $unit->getAttribute("{$type}_original_name") ?: basename($path);

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($path, now()->addMinutes(10)));
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Response::streamDownload(fn () => print $disk->get($path), $name);
        } catch (\Throwable) {
            abort(404);
        }
    }

    public function picture(Unit $unit, int $index)
    {
        $this->authorizeOwnerUnit($unit);

        $picture = collect($unit->pictures ?? [])->get($index);
        abort_if(! $picture || empty($picture['path']), 404);

        $disk = Storage::disk($picture['disk'] ?? config('filesystems.default'));
        $path = $picture['path'];

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($path, now()->addMinutes(10)));
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Response::streamDownload(fn () => print $disk->get($path), $picture['name'] ?? basename($path));
        } catch (\Throwable) {
            abort(404);
        }
    }

    public function sendAccessCardRequest(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['New card', 'Lost card', 'Replacement card'])],
            'card_type' => ['required', Rule::in(['Access card', 'Parking card', 'Access and parking card'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $unit->load(['building', 'owners']);

        $securityEmails = collect($unit->building->security_emails ?? [])
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values();

        if ($securityEmails->isEmpty()) {
            throw ValidationException::withMessages([
                'security_emails' => 'Add security email addresses on the building before sending an access card request.',
            ]);
        }

        $primaryOwner = $unit->owners
            ->sortByDesc(fn ($owner) => (float) ($owner->pivot?->share_percent ?? 0))
            ->first();

        if (! $unit->title_deed_path) {
            throw ValidationException::withMessages([
                'title_deed' => 'Upload the unit title deed before sending an access card request.',
            ]);
        }

        if (! $primaryOwner?->document_path) {
            throw ValidationException::withMessages([
                'owner_document' => 'Upload the primary owner passport or Emirates ID before sending an access card request.',
            ]);
        }

        Mail::to($securityEmails->all())->queue(new UnitAccessCardRequestMail(
            unit: $unit,
            requestType: $validated['request_type'],
            cardType: $validated['card_type'],
            notes: $validated['notes'] ?? null,
        ));

        ActivityLogger::log('units.access_card_request_sent', "Queued {$validated['card_type']} {$validated['request_type']} email for unit {$unit->unit_no}.", $unit);

        return back()->with('status', 'Access card request email queued for building security.');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', array_merge($this->formData(), [
            'unit' => $unit->load(['owners', 'utilityAccounts']),
        ]));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $this->validated($request, $unit);
        $validated = $this->normalize($request, $validated, $unit);
        $validated['updated_by'] = auth()->id();
        $validated = array_merge($validated, $this->uploadedDocuments($request, $unit));
        unset($validated['owners'], $validated['owner_shares'], $validated['ownership_rows'], $validated['utility_accounts'], $validated['title_deed'], $validated['dtcm_permit'], $validated['ttlock_attachment'], $validated['pictures_upload']);

        $unit->update($validated);
        $this->syncOwners($unit, $request);
        $this->syncUtilityAccounts($unit, $request);

        ActivityLogger::log('units.updated', "Updated unit {$unit->unit_no}.", $unit);

        return redirect()->route('units.show', $unit)->with('status', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit)
    {
        ActivityLogger::log('units.deleted', "Deleted unit {$unit->unit_no}.", $unit);
        $unit->delete();

        return redirect()->route('units.index')->with('status', 'Unit deleted successfully.');
    }

    private function formData(): array
    {
        return [
            'buildings' => Building::query()->orderBy('name')->get(),
            'owners' => Owner::query()->orderBy('full_name')->get(),
            'unitTypes' => Unit::TYPES,
            'availabilityStatuses' => Unit::AVAILABILITY_STATUSES,
            'rentPeriods' => Unit::RENT_PERIODS,
            'utilityProviderTypes' => UtilityAccount::PROVIDER_TYPES,
            'ttLocks' => TtLock::query()
                ->with('unit.building')
                ->where(fn ($query) => $query->whereDoesntHave('unit')->orWhere('id', request()->route('unit')?->tt_lock_id))
                ->orderBy('lock_name')
                ->get(),
        ];
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $validated = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'unit_no' => ['required', 'string', 'max:191', Rule::unique('units', 'unit_no')->where('building_id', $request->input('building_id'))->ignore($unit)],
            'unit_type' => ['required', 'string', 'max:191'],
            'availability_status' => ['required', Rule::in(Unit::AVAILABILITY_STATUSES)],
            'floor' => ['nullable', 'string', 'max:50'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'size_sqft' => ['nullable', 'numeric', 'min:0'],
            'view' => ['nullable', 'string', 'max:191'],
            'parking_no' => ['nullable', 'string', 'max:191'],
            'wifi_name' => ['nullable', 'string', 'max:191'],
            'wifi_password' => ['nullable', 'string', 'max:191'],
            'management_fee_percent' => ['nullable', 'numeric', 'between:0,100'],
            'rent_period' => ['required', Rule::in(Unit::RENT_PERIODS)],
            'rent_amount' => ['nullable', 'numeric', 'min:0'],
            'amenities' => ['nullable', 'string', 'max:4000'],
            'internet_provider' => ['nullable', Rule::in(['etisalat', 'du'])],
            'internet_account_no' => ['nullable', 'string', 'max:191'],
            'electricity_company' => ['nullable', 'string', 'max:191'],
            'electricity_paid_by_us' => ['nullable', 'boolean'],
            'electricity_username' => ['nullable', 'string', 'max:191'],
            'electricity_password' => ['nullable', 'string', 'max:191'],
            'gas_company' => ['nullable', 'string', 'max:191'],
            'gas_details' => ['nullable', 'string', 'max:2000'],
            'hvac_details' => ['nullable', 'string', 'max:2000'],
            'other_utility_details' => ['nullable', 'string', 'max:2000'],
            'title_deed_no' => ['nullable', 'string', 'max:191'],
            'title_deed_expiry_date' => ['nullable', 'date'],
            'dtcm_permit_no' => ['nullable', 'string', 'max:191'],
            'dtcm_permit_expiry_date' => ['nullable', 'date'],
            'tt_lock_id' => ['nullable', 'exists:tt_locks,id'],
            'ttlock_settings' => ['nullable', 'string', 'max:4000'],
            'ttlock_locks' => ['nullable', 'array'],
            'ttlock_locks.*.name' => ['nullable', 'string', 'max:191'],
            'ttlock_locks.*.lock_id' => ['nullable', 'string', 'max:191'],
            'ttlock_locks.*.gateway_id' => ['nullable', 'string', 'max:191'],
            'ttlock_locks.*.passcode' => ['nullable', 'string', 'max:191'],
            'ttlock_locks.*.status' => ['nullable', 'string', 'max:191'],
            'ttlock_locks.*.notes' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'owners' => ['nullable', 'array'],
            'owners.*' => ['nullable', 'exists:owners,id'],
            'owner_shares' => ['nullable', 'array'],
            'owner_shares.*' => ['nullable', 'numeric', 'between:0,100'],
            'ownership_rows' => ['nullable', 'array'],
            'ownership_rows.*.owner_id' => ['nullable', 'exists:owners,id'],
            'ownership_rows.*.share_percent' => ['nullable', 'numeric', 'between:0,100'],
            'utility_accounts' => ['nullable', 'array'],
            'utility_accounts.*.id' => ['nullable', 'exists:utility_accounts,id'],
            'utility_accounts.*.provider_type' => ['nullable', Rule::in(UtilityAccount::PROVIDER_TYPES)],
            'utility_accounts.*.provider_name' => ['nullable', 'string', 'max:191'],
            'utility_accounts.*.account_no' => ['nullable', 'string', 'max:191'],
            'utility_accounts.*.username' => ['nullable', 'string', 'max:191'],
            'utility_accounts.*.password' => ['nullable', 'string', 'max:191'],
            'utility_accounts.*.billing_day' => ['nullable', 'integer', 'between:1,31'],
            'utility_accounts.*.next_due_date' => ['nullable', 'date'],
            'utility_accounts.*.estimated_amount' => ['nullable', 'numeric', 'min:0'],
            'utility_accounts.*.paid_by_company' => ['nullable', 'boolean'],
            'utility_accounts.*.notes' => ['nullable', 'string', 'max:1000'],
            'title_deed' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'dtcm_permit' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'ttlock_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'pictures_upload.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $ownershipRows = collect($request->input('ownership_rows', []))
            ->filter(fn (array $row): bool => ! empty($row['owner_id']));

        if ($ownershipRows->isNotEmpty()) {
            $totalShare = $ownershipRows->sum(fn (array $row): float => (float) ($row['share_percent'] ?? 0));

            if (abs($totalShare - 100) >= 0.01) {
                throw ValidationException::withMessages([
                    'ownership_rows' => 'Active ownership shares must total exactly 100%.',
                ]);
            }
        }

        return $validated;
    }

    private function normalize(Request $request, array $validated, ?Unit $unit = null): array
    {
        $validated['amenities'] = $this->lines($request->string('amenities')->toString());
        $validated['ttlock_settings'] = [];
        $validated['ttlock_locks'] = [];
        $validated['electricity_paid_by_us'] = $request->boolean('electricity_paid_by_us');
        $validated['pictures'] = $unit?->pictures ?? [];

        if (! $validated['electricity_paid_by_us']) {
            $validated['electricity_username'] = null;
            $validated['electricity_password'] = null;
        }

        return $validated;
    }

    private function uploadedDocuments(Request $request, ?Unit $unit = null): array
    {
        $data = [];

        foreach (['title_deed' => 'Title Deed', 'dtcm_permit' => 'DTCM Permit', 'ttlock_attachment' => 'TT Lock'] as $field => $label) {
            if ($request->hasFile($field)) {
                $data = array_merge($data, $this->storeFile($request->file($field), $request, $field, "{$label} - Unit {$request->input('unit_no')}"));

                if ($unit?->getAttribute("{$field}_path")) {
                    Storage::disk($unit->getAttribute("{$field}_disk") ?? config('filesystems.default'))->delete($unit->getAttribute("{$field}_path"));
                }
            }
        }

        $pictures = $unit?->pictures ?? [];
        foreach ($request->file('pictures_upload', []) as $picture) {
            $stored = $this->storePicture($picture, $request);
            $pictures[] = $stored;
        }
        $data['pictures'] = $pictures;

        return $data;
    }

    private function storeFile(UploadedFile $file, Request $request, string $field, string $displayName): array
    {
        $disk = config('filesystems.default');
        $unitName = $request->input('unit_no', 'unit');
        $path = ErpStoragePath::documentPath('Units', $unitName, str_replace('_', '-', $field), $file, $displayName.'.'.$file->getClientOriginalExtension());

        try {
            $stored = Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
        } catch (\Throwable $exception) {
            report($exception);
            $stored = false;
        }

        if (! $stored) {
            throw ValidationException::withMessages([$field => 'The file could not be uploaded. Please check storage settings and try again.']);
        }

        return [
            "{$field}_disk" => $disk,
            "{$field}_path" => $path,
            "{$field}_original_name" => $displayName.'.'.$file->getClientOriginalExtension(),
        ];
    }

    private function storePicture(UploadedFile $file, Request $request): array
    {
        $disk = config('filesystems.default');
        $path = ErpStoragePath::documentPath('Units', $request->input('unit_no', 'unit'), 'pictures', $file, 'Picture - Unit '.$request->input('unit_no').'-'.Str::random(6).'.'.$file->getClientOriginalExtension());

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        return ['disk' => $disk, 'path' => $path, 'name' => basename($path)];
    }

    private function syncOwners(Unit $unit, Request $request): void
    {
        $sync = [];

        if ($request->has('ownership_rows')) {
            foreach ($request->input('ownership_rows', []) as $row) {
                $ownerId = $row['owner_id'] ?? null;

                if ($ownerId) {
                    $sync[$ownerId] = ['share_percent' => $row['share_percent'] ?? 100];
                }
            }
        } else {
            foreach ($request->input('owners', []) as $ownerId) {
                if ($ownerId) {
                    $sync[$ownerId] = ['share_percent' => $request->input("owner_shares.{$ownerId}", 100)];
                }
            }
        }

        $unit->owners()->sync($sync);
    }

    private function syncUtilityAccounts(Unit $unit, Request $request): void
    {
        $submittedIds = [];

        foreach ($request->input('utility_accounts', []) as $row) {
            $hasUsefulData = collect([
                $row['provider_type'] ?? null,
                $row['provider_name'] ?? null,
                $row['account_no'] ?? null,
                $row['username'] ?? null,
                $row['password'] ?? null,
                $row['billing_day'] ?? null,
                $row['next_due_date'] ?? null,
                $row['estimated_amount'] ?? null,
                $row['notes'] ?? null,
            ])->filter(fn ($value) => filled($value))->isNotEmpty();

            if (! $hasUsefulData) {
                continue;
            }

            $account = null;

            if (! empty($row['id'])) {
                $account = $unit->utilityAccounts()->whereKey($row['id'])->first();
            }

            $account ??= new UtilityAccount(['unit_id' => $unit->id]);

            $account->fill([
                'provider_type' => $row['provider_type'] ?? 'other',
                'provider_name' => trim($row['provider_name'] ?? '') ?: str($row['provider_type'] ?? 'other')->headline()->toString(),
                'account_no' => $row['account_no'] ?? null,
                'username' => $row['username'] ?? null,
                'password' => $row['password'] ?? null,
                'billing_day' => $row['billing_day'] ?? null,
                'next_due_date' => $row['next_due_date'] ?? null,
                'estimated_amount' => $row['estimated_amount'] ?? null,
                'paid_by_company' => (bool) ($row['paid_by_company'] ?? false),
                'status' => 'active',
                'notes' => $row['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            if (! $account->exists) {
                $account->created_by = auth()->id();
            }

            $unit->utilityAccounts()->save($account);
            $submittedIds[] = $account->id;
        }

        if ($request->has('utility_accounts_present')) {
            $query = $unit->utilityAccounts();

            if ($submittedIds) {
                $query->whereNotIn('id', $submittedIds);
            }

            $query->delete();
        }
    }

    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function locks(array $locks): array
    {
        return collect($locks)
            ->map(fn (array $lock): array => [
                'name' => trim($lock['name'] ?? ''),
                'lock_id' => trim($lock['lock_id'] ?? ''),
                'gateway_id' => trim($lock['gateway_id'] ?? ''),
                'passcode' => trim($lock['passcode'] ?? ''),
                'status' => trim($lock['status'] ?? ''),
                'notes' => trim($lock['notes'] ?? ''),
            ])
            ->filter(fn (array $lock): bool => collect($lock)->filter()->isNotEmpty())
            ->take(1)
            ->values()
            ->all();
    }

    private function currentOwner(): ?Owner
    {
        $user = auth()->user();

        if (! $user?->hasRole('Owner')) {
            return null;
        }

        return Owner::query()
            ->where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();
    }

    private function authorizeOwnerUnit(Unit $unit): void
    {
        $owner = $this->currentOwner();

        if (! $owner) {
            return;
        }

        abort_unless($unit->owners()->whereKey($owner->id)->exists(), 403);
    }
}
