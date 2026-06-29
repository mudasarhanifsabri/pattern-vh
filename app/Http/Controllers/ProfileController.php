<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'tenant' => $this->tenantFor($request),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updateTenantBankDetails(Request $request): RedirectResponse
    {
        $tenant = $this->tenantFor($request);
        abort_unless($tenant, 403);

        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_account_name' => ['required', 'string', 'max:191'],
            'bank_account_no' => ['nullable', 'string', 'max:191'],
            'iban' => ['required', 'string', 'max:191'],
            'swift_code' => ['nullable', 'string', 'max:191'],
        ]);

        $tenant->update($validated);

        return Redirect::route('profile.edit')->with('status', 'bank-details-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function tenantFor(Request $request): ?Tenant
    {
        if (! $request->user()?->can('portal.tenant')) {
            return null;
        }

        return Tenant::query()
            ->where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->first();
    }
}
