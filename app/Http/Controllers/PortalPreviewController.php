<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\OperationsTeamMember;
use App\Models\Owner;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class PortalPreviewController extends Controller
{
    public function start(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403, 'Only Super Admin can preview portals.');

        $record = $this->recordFor($type, $id);
        $role = $this->roleFor($type, $record);
        $user = $this->portalUserFor($record, $role);

        if (! $request->session()->has('portal_preview_admin_id')) {
            $request->session()->put('portal_preview_admin_id', $request->user()->id);
        }

        $request->session()->put('portal_preview_record', [
            'name' => $record->full_name,
            'role' => $role,
        ]);

        ActivityLogger::log('portal_preview.started', "Super Admin opened {$role} portal for {$record->full_name}.", $record, [
            'preview_user_id' => $user->id,
            'admin_user_id' => $request->user()->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', "Viewing {$record->full_name} as {$role}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('portal_preview_admin_id');
        $request->session()->forget('portal_preview_record');

        abort_unless($adminId, 403);

        $admin = User::query()->findOrFail($adminId);
        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Returned to Super Admin.');
    }

    private function recordFor(string $type, int $id): Model
    {
        $modelClass = match ($type) {
            'owner', 'owners' => Owner::class,
            'tenant', 'tenants' => Tenant::class,
            'agent', 'agents' => Agent::class,
            'operations-team' => OperationsTeamMember::class,
            default => abort(404),
        };

        return $modelClass::query()->findOrFail($id);
    }

    private function roleFor(string $type, Model $record): string
    {
        if ($type !== 'operations-team') {
            return match ($type) {
                'owner', 'owners' => 'Owner',
                'tenant', 'tenants' => 'Tenant',
                'agent', 'agents' => 'Agent',
            };
        }

        return match ($record->team_role) {
            'cleaner' => 'Cleaner',
            'technician' => 'Technician',
            default => 'Operations Team',
        };
    }

    private function portalUserFor(Model $record, string $role): User
    {
        if (! $record->email) {
            throw ValidationException::withMessages([
                'email' => 'Add an email address before opening this portal.',
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

        $user->forceFill([
            'name' => $record->full_name,
            'email' => $record->email,
        ])->save();

        $user->assignRole(Role::findOrCreate($role, 'web'));
        $record->forceFill(['user_id' => $user->id])->save();

        return $user;
    }
}
