<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->with('roles')
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
            'userRoles' => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:191', 'unique:users,email'],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'send_password_setup' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $sendPasswordSetup = $request->boolean('send_password_setup');

        if (! $sendPasswordSetup && empty($validated['password'])) {
            return back()
                ->withErrors(['password' => 'Enter a password or choose to send the setup email now.'])
                ->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'] ?? Str::random(40)),
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        if ($sendPasswordSetup) {
            Password::sendResetLink(['email' => $user->email]);
        }

        ActivityLogger::log('users.created', "Created user {$user->name}.", $user, [
            'roles' => $user->getRoleNames()->all(),
            'password_setup_email_sent' => $sendPasswordSetup,
        ]);

        $message = $sendPasswordSetup
            ? 'User created successfully. Password setup email was sent.'
            : 'User created successfully.';

        return redirect()->route('admin.users.index')->with('status', $message);
    }

    public function show(User $user)
    {
        return redirect()->route('admin.users.edit', $user);
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
            'userRoles' => $user->getRoleNames(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles($validated['roles'] ?? []);

        ActivityLogger::log('users.updated', "Updated user {$user->name}.", $user, [
            'roles' => $user->getRoleNames()->all(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'You cannot delete your own account.');

        $name = $user->name;
        ActivityLogger::log('users.deleted', "Deleted user {$name}.", $user, [
            'email' => $user->email,
            'roles' => $user->getRoleNames()->all(),
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }
}
