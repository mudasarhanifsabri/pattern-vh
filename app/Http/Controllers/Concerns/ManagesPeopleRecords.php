<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use App\Notifications\WelcomePasswordSetupNotification;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
use App\Support\PeopleDuplicateGuard;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

trait ManagesPeopleRecords
{
    abstract protected function modelClass(): string;

    abstract protected function moduleConfig(): array;

    public function index()
    {
        $config = $this->moduleConfig();
        $modelClass = $this->modelClass();

        $records = $modelClass::query()
            ->withCount('notes')
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('mobile_no', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('identity_no', 'like', "%{$search}%");
                });
            })
            ->when(request('status') === 'blacklisted', fn ($query) => $query->where('is_blacklisted', true))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('people.index', compact('records', 'config'));
    }

    public function create()
    {
        return view('people.create', [
            'record' => null,
            'config' => $this->moduleConfig(),
        ]);
    }

    public function store(Request $request, PeopleDuplicateGuard $duplicates)
    {
        $config = $this->moduleConfig();
        $modelClass = $this->modelClass();
        $validated = $this->validated($request);
        $validated['mobile_has_whatsapp'] = $request->boolean('mobile_has_whatsapp');
        $validated['is_blacklisted'] = $request->boolean('is_blacklisted');
        $validated = $this->normalizeModuleBooleans($request, $validated);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        [$record, $created] = $duplicates->withCreateLock($modelClass, $validated, function () use ($request, $config, $modelClass, $validated, $duplicates): array {
            if ($existing = $duplicates->findDuplicate($modelClass, $validated)) {
                return [$existing, false];
            }

            if ($request->hasFile('document')) {
                $validated = array_merge($validated, $this->storeDocument($request, $config));
            }

            $note = $validated['note'] ?? null;
            unset($validated['document'], $validated['note'], $validated['send_portal_invite']);

            $record = $modelClass::create($validated);
            $this->storeNoteIfPresent($record, $note);

            ActivityLogger::log($config['route'].'.created', "Created {$config['singular']} {$record->full_name}.", $record);

            return [$record, true];
        });

        if ($created && $request->boolean('send_portal_invite')) {
            $this->sendPortalInvite($record, $config);
        }

        return redirect()
            ->route($config['route'].'.show', $record)
            ->with('status', $created ? "{$config['singularTitle']} created successfully." : "{$config['singularTitle']} already exists. Opened the existing record instead of creating a duplicate.");
    }

    public function show($record)
    {
        $record = $this->resolveRecord($record);
        $relations = ['notes.user', 'creator', 'updater', 'user'];

        if (($this->moduleConfig()['extra'] ?? null) === 'tenant') {
            $relations['bookings'] = fn ($query) => $query->with(['unit.building'])->latest('check_in_date');
        }

        return view('people.show', [
            'record' => $record->load($relations),
            'config' => $this->moduleConfig(),
        ]);
    }

    public function document($record)
    {
        $record = $this->resolveRecord($record);
        abort_if(! $record->document_path, 404);

        $disk = Storage::disk($record->document_disk ?? config('filesystems.default'));

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($record->document_path, now()->addMinutes(10)));
            } catch (\Throwable) {
                //
            }
        }

        try {
            return Response::streamDownload(fn () => print $disk->get($record->document_path), $record->document_original_name ?: basename($record->document_path));
        } catch (\Throwable) {
            abort(404);
        }
    }

    public function edit($record)
    {
        $record = $this->resolveRecord($record);

        return view('people.edit', [
            'record' => $record,
            'config' => $this->moduleConfig(),
        ]);
    }

    public function update(Request $request, $record, PeopleDuplicateGuard $duplicates)
    {
        $record = $this->resolveRecord($record);
        $config = $this->moduleConfig();
        $validated = $this->validated($request);
        $validated['mobile_has_whatsapp'] = $request->boolean('mobile_has_whatsapp');
        $validated['is_blacklisted'] = $request->boolean('is_blacklisted');
        $validated = $this->normalizeModuleBooleans($request, $validated);
        $validated['updated_by'] = auth()->id();

        if ($existing = $duplicates->findDuplicate($this->modelClass(), $validated, $record->id)) {
            throw ValidationException::withMessages([
                'full_name' => "This {$config['singular']} looks like a duplicate of {$existing->full_name}. Open the existing record instead.",
            ]);
        }

        if (! $validated['is_blacklisted']) {
            $validated['blacklist_reason'] = null;
        }

        if ($request->hasFile('document')) {
            if ($record->document_path) {
                Storage::disk($record->document_disk ?? config('filesystems.default'))->delete($record->document_path);
            }

            $validated = array_merge($validated, $this->storeDocument($request, $config));
        }

        $note = $validated['note'] ?? null;
        $sendPortalInvite = $request->boolean('send_portal_invite');
        unset($validated['document'], $validated['note'], $validated['send_portal_invite']);

        $record->update($validated);

        if ($sendPortalInvite) {
            $this->sendPortalInvite($record->fresh(), $config);
        }

        $this->storeNoteIfPresent($record, $note);

        ActivityLogger::log($config['route'].'.updated', "Updated {$config['singular']} {$record->full_name}.", $record);

        return redirect()->route($config['route'].'.show', $record)->with('status', "{$config['singularTitle']} updated successfully.");
    }

    public function destroy($record)
    {
        $record = $this->resolveRecord($record);
        $config = $this->moduleConfig();
        ActivityLogger::log($config['route'].'.deleted', "Deleted {$config['singular']} {$record->full_name}.", $record);
        $record->delete();

        return redirect()->route($config['route'].'.index')->with('status', "{$config['singularTitle']} deleted successfully.");
    }

    public function sendInvite($record)
    {
        $record = $this->resolveRecord($record);
        $config = $this->moduleConfig();
        $this->sendPortalInvite($record, $config);

        return redirect()->route($config['route'].'.show', $record)->with('status', "{$config['singularTitle']} welcome email queued successfully.");
    }

    public function storeNote(Request $request, $record)
    {
        $record = $this->resolveRecord($record);
        $request->validate(['note' => ['required', 'string', 'max:4000']]);
        $this->storeNoteIfPresent($record, $request->string('note')->toString());

        return back()->with('status', 'Note added successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate(array_merge([
            'full_name' => ['required', 'string', 'max:255'],
            'mobile_no' => ['required', 'string', 'max:30'],
            'mobile_has_whatsapp' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:191'],
            'identity_type' => ['required', Rule::in(['passport', 'emirates_id'])],
            'identity_no' => ['nullable', 'string', 'max:191'],
            'identity_issue_date' => ['nullable', 'date'],
            'identity_expiry_date' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'is_blacklisted' => ['nullable', 'boolean'],
            'blacklist_reason' => ['nullable', 'required_if:is_blacklisted,1', 'string', 'max:2000'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_account_name' => ['nullable', 'string', 'max:191'],
            'bank_account_no' => ['nullable', 'string', 'max:191'],
            'iban' => ['nullable', 'string', 'max:191'],
            'swift_code' => ['nullable', 'string', 'max:191'],
            'note' => ['nullable', 'string', 'max:4000'],
            'send_portal_invite' => ['nullable', 'boolean'],
        ], $this->moduleConfig()['rules'] ?? []));
    }

    private function storeDocument(Request $request, array $config): array
    {
        $file = $request->file('document');
        $disk = config('filesystems.default');
        $documentName = $this->documentName($request, $file);
        $path = ErpStoragePath::documentPath($config['storage'], $request->string('full_name')->toString(), 'identity-documents', $file, $documentName);

        try {
            $stored = Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
        } catch (\Throwable $exception) {
            report($exception);
            $stored = false;
        }

        if (! $stored) {
            throw ValidationException::withMessages([
                'document' => 'The document could not be uploaded. Please check the AWS S3 bucket settings and try again.',
            ]);
        }

        return [
            'document_disk' => $disk,
            'document_path' => $path,
            'document_original_name' => $documentName,
        ];
    }

    private function documentName(Request $request, UploadedFile $file): string
    {
        $type = $request->string('identity_type')->toString() === 'passport' ? 'Passport' : 'Emirates ID';
        $name = Str::of($request->string('full_name')->toString())->squish()->whenEmpty(fn () => 'Person');

        return "{$type} - {$name}.{$file->getClientOriginalExtension()}";
    }

    private function sendPortalInvite($record, array $config): void
    {
        if (! $record->email) {
            throw ValidationException::withMessages([
                'email' => "Add a {$config['singular']} email before sending the portal welcome email.",
            ]);
        }

        $user = $record->user ?: User::firstOrCreate(
            ['email' => $record->email],
            [
                'name' => $record->full_name,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill(['name' => $record->full_name, 'email' => $record->email])->save();
        $user->assignRole(Role::findOrCreate($config['role'], 'web'));
        $record->forceFill(['user_id' => $user->id])->save();

        $token = Password::broker()->createToken($user);
        $user->notify(new WelcomePasswordSetupNotification($token, $config['portal']));

        $record->forceFill(['portal_invitation_sent_at' => now()])->save();
    }

    private function storeNoteIfPresent($record, ?string $note): void
    {
        if (! $note) {
            return;
        }

        $record->notes()->create([
            'user_id' => auth()->id(),
            'note' => $note,
        ]);
    }

    private function normalizeModuleBooleans(Request $request, array $validated): array
    {
        if (($this->moduleConfig()['extra'] ?? null) !== 'operations') {
            return $validated;
        }

        foreach (['auto_assign_checkout_cleaning', 'auto_assign_checkout_inspection', 'auto_assign_stay_tasks'] as $field) {
            $validated[$field] = $request->boolean($field);
        }

        return $validated;
    }

    private function resolveRecord($record)
    {
        if (is_object($record)) {
            return $record;
        }

        $modelClass = $this->modelClass();

        return $modelClass::query()->findOrFail($record);
    }
}
