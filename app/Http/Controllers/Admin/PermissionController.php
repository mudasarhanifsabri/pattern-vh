<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return view('admin.permissions.index', [
            'permissions' => Permission::query()->orderBy('name')->get()->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->headline()->toString()),
        ]);
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:125', Rule::unique('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        ActivityLogger::log('permissions.created', "Created permission {$permission->name}.", $permission);

        return redirect()->route('admin.permissions.index')->with('status', 'Permission created successfully.');
    }

    public function show(Permission $permission)
    {
        return redirect()->route('admin.permissions.edit', $permission);
    }

    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:125', Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission)],
        ]);

        $permission->update(['name' => $validated['name']]);

        ActivityLogger::log('permissions.updated', "Updated permission {$permission->name}.", $permission);

        return redirect()->route('admin.permissions.index')->with('status', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission)
    {
        $name = $permission->name;
        ActivityLogger::log('permissions.deleted', "Deleted permission {$name}.", $permission);
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('status', 'Permission deleted successfully.');
    }
}
