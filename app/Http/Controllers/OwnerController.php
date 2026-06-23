<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\User;
use App\Notifications\WelcomePasswordSetupNotification;
use App\Support\ActivityLogger;
use App\Support\ErpStoragePath;
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

class OwnerController extends Controller
{
    public function index()
    {
        $owners = Owner::query()
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

        return view('owners.index', compact('owners'));
    }

    public function create()
    {
        return view('owners.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateOwner($request);
        $validated['mobile_has_whatsapp'] = $request->boolean('mobile_has_whatsapp');
        $validated['is_blacklisted'] = $request->boolean('is_blacklisted');
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('document')) {
            $validated = array_merge($validated, $this->storeDocument($request));
        }

        $note = $validated['note'] ?? null;
        $sendPortalInvite = $request->boolean('send_portal_invite');
        unset($validated['document'], $validated['note'], $validated['send_portal_invite']);

        $owner = Owner::create($validated);

        if ($sendPortalInvite) {
            $this->sendPortalInvite($owner);
        }

        if ($note) {
            $owner->notes()->create([
                'user_id' => auth()->id(),
                'note' => $note,
            ]);
        }

        ActivityLogger::log('owners.created', "Created owner {$owner->full_name}.", $owner);

        return redirect()->route('owners.show', $owner)->with('status', 'Owner created successfully.');
    }

    public function show(Owner $owner)
    {
        return view('owners.show', [
            'owner' => $owner->load([
                'notes.user',
                'creator',
                'updater',
                'user',
                'unitContracts.unit.building',
                'units' => fn ($query) => $query->with('building')->withCount('bookings')->orderBy('unit_no'),
            ]),
        ]);
    }

    public function document(Owner $owner)
    {
        abort_if(! $owner->document_path, 404);

        $disk = Storage::disk($owner->document_disk ?? config('filesystems.default'));

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return redirect()->away($disk->temporaryUrl($owner->document_path, now()->addMinutes(10)));
            } catch (\Throwable) {
                // Fall back to a streamed download for disks that cannot generate temporary URLs.
            }
        }

        try {
            return Response::streamDownload(function () use ($disk, $owner): void {
                echo $disk->get($owner->document_path);
            }, $owner->document_original_name ?: basename($owner->document_path));
        } catch (\Throwable) {
            abort(404);
        }
    }

    public function edit(Owner $owner)
    {
        return view('owners.edit', compact('owner'));
    }

    public function update(Request $request, Owner $owner)
    {
        $validated = $this->validateOwner($request);
        $validated['mobile_has_whatsapp'] = $request->boolean('mobile_has_whatsapp');
        $validated['is_blacklisted'] = $request->boolean('is_blacklisted');
        $validated['updated_by'] = auth()->id();

        if (! $validated['is_blacklisted']) {
            $validated['blacklist_reason'] = null;
        }

        if ($request->hasFile('document')) {
            if ($owner->document_path) {
                Storage::disk($owner->document_disk ?? config('filesystems.default'))->delete($owner->document_path);
            }

            $validated = array_merge($validated, $this->storeDocument($request));
        }

        $note = $validated['note'] ?? null;
        $sendPortalInvite = $request->boolean('send_portal_invite');
        unset($validated['document'], $validated['note'], $validated['send_portal_invite']);

        $owner->update($validated);

        if ($sendPortalInvite) {
            $this->sendPortalInvite($owner->fresh());
        }

        if ($note) {
            $owner->notes()->create([
                'user_id' => auth()->id(),
                'note' => $note,
            ]);
        }

        ActivityLogger::log('owners.updated', "Updated owner {$owner->full_name}.", $owner);

        return redirect()->route('owners.show', $owner)->with('status', 'Owner updated successfully.');
    }

    public function destroy(Owner $owner)
    {
        ActivityLogger::log('owners.deleted', "Deleted owner {$owner->full_name}.", $owner);
        $owner->delete();

        return redirect()->route('owners.index')->with('status', 'Owner deleted successfully.');
    }

    public function sendInvite(Owner $owner)
    {
        $this->sendPortalInvite($owner);

        return redirect()->route('owners.show', $owner)->with('status', 'Owner welcome email queued successfully.');
    }

    private function validateOwner(Request $request): array
    {
        return $request->validate([
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
        ]);
    }

    private function storeDocument(Request $request): array
    {
        $file = $request->file('document');
        $disk = config('filesystems.default');
        $documentName = $this->documentName($request, $file);
        $path = ErpStoragePath::documentPath('Owners', $request->string('full_name')->toString(), 'identity-documents', $file, $documentName);

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
        $type = $request->string('identity_type')->toString() === 'passport'
            ? 'Passport'
            : 'Emirates ID';

        $ownerName = Str::of($request->string('full_name')->toString())
            ->squish()
            ->whenEmpty(fn () => 'Owner');

        return "{$type} - {$ownerName}.{$file->getClientOriginalExtension()}";
    }

    private function sendPortalInvite(Owner $owner): void
    {
        if (! $owner->email) {
            throw ValidationException::withMessages([
                'email' => 'Add an owner email before sending the portal welcome email.',
            ]);
        }

        $user = $owner->user ?: User::firstOrCreate(
            ['email' => $owner->email],
            [
                'name' => $owner->full_name,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill([
            'name' => $owner->full_name,
            'email' => $owner->email,
        ])->save();

        $user->assignRole(Role::findOrCreate('Owner', 'web'));

        $owner->forceFill(['user_id' => $user->id])->save();

        $token = Password::broker()->createToken($user);
        $user->notify(new WelcomePasswordSetupNotification($token, 'Pattern RMS Owner Portal'));

        $owner->forceFill(['portal_invitation_sent_at' => now()])->save();

        ActivityLogger::log('owners.portal_invite_sent', "Sent owner portal welcome email to {$owner->full_name}.", $owner, [
            'email' => $owner->email,
            'user_id' => $user->id,
        ]);
    }
}
